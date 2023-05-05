<?php
declare(strict_types=1);

namespace Lqdt\CakephpApie\Controller\Component;

use Cake\Controller\Component;
use Cake\Database\Expression\FunctionExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\ServerRequest;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Query;
use Cake\ORM\Table;
use InvalidArgumentException;

class ApiComponent extends Component
{
    use LocatorAwareTrait;

    /**
     * Stores allowed models for unions
     *
     * @var string[]
     */
    protected $_allowed = [];

    /**
     * Stores base model to use for database operations
     *
     * @var \Cake\ORM\Table
     */
    protected $_model;

    /**
     * Stores query parameter name
     *
     * @var string
     */
    protected $_queryParam = 'q';

    /**
     * Flag used to disable association allowance check
     *
     * @var bool
     */
    protected $_allowAll = false;

    /**
     * Initialize component and set options
     *
     * @param array $config Configuration for component
     * @return void
     */
    public function initialize(array $config = []): void
    {
        $this->_queryParam = $config['q'] ?? 'q';
        $this->_allowAll = $config['allowAll'] ?? false;
    }

    /**
     * Returns the current model used or raises an exception if missing
     *
     * @return \Cake\ORM\Table
     * @throws \Cake\Http\Exception\BadRequestException If no model configured
     */
    public function getModel(): Table
    {
        if ($this->_model === null) {
            throw new BadRequestException('Missing model');
        }

        return $this->_model;
    }

    /**
     * Configures the query parameter name to look for
     *
     * @param string $name Param name
     * @return self
     */
    public function setQueryParam(string $name): self
    {
        $this->_queryParam = $name;

        return $this;
    }

    /**
     * Use a given model
     *
     * You can either provide an instance of Cake\ORM\Table or a string that will be resolved from table registry
     *
     * @param string|\Cake\ORM\Table $model Model to use
     * @return self
     */
    public function use($model): self
    {
        if (is_string($model)) {
            $model = $this->getTableLocator()->get($model);
        }

        if (!($model instanceof Table)) {
            throw new InvalidArgumentException('[ApiComponent] Provided model to use is not valid ');
        }

        $this->_model = $model;

        return $this;
    }

    /**
     * Provides allowed models for associations
     *
     * @param string|string[] $models Allowed models
     * @param bool $override If `true` current allowed models will be replaced by provided ones
     * @return self
     */
    public function allow($models, bool $override = false): self
    {
        if (!is_array($models)) {
            $models = [$models];
        }

        if (!$override) {
            $models = $this->_allowed + $models;
        }

        $this->_allowed = $models;

        return $this;
    }

    /**
     * Disable or enable association check for this component instance
     *
     * @param bool $enabled If `true`, association check will be disabled
     * @return self
     */
    public function allowAll(bool $enabled = true): self
    {
        $this->_allowAll = $enabled;

        return $this;
    }

    /**
     * Checks if a required model path is within allowed paths
     *
     * @param string $path Path to check
     * @return bool
     */
    public function isAllowed(string $path): bool
    {
        if ($this->_allowAll) {
            return true;
        }

        foreach ($this->_allowed as $p) {
            if (strpos($p, $path) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns a query which have been configured based on query descriptor
     *
     * @param string $type  Finder to use
     * @param array<string, mixed> $options Options for query
     * @return \Cake\ORM\Query Initialized query
     */
    public function find(string $type = 'all', array $options = []): Query
    {
        $query = $this->getModel()->find($type, $options);
        /** @var string $q */
        $q = $this->_getRequest()->getQuery($this->_queryParam) ?? '{}';
        $descriptor = json_decode($q, true);

        $this->configure($query, $descriptor);

        return $query;
    }

    /**
     * Processes a query accordingly to the provided descriptor
     *
     * @param \Cake\ORM\Query $query Query to configure
     * @param array $descriptor Query descriptor
     * @return \Cake\ORM\Query Configured query
     */
    public function configure(Query &$query, array $descriptor): Query
    {
        foreach ($descriptor as $key => $nodes) {
            $key = trim($key, '+');

            if (!method_exists($query, $key)) {
                throw new BadRequestException("Unknown method {$key} requested on Query");
            }

            $args = $this->_processArguments($nodes, $query, $key);
            /** @phpstan-ignore-next-line */
            $query = call_user_func_array([$query,$key], $args);
        }

        // Ensures that requested associations are allowed
        foreach ($query->getEagerLoader()->associationsMap($query->getRepository()) as $m) {
            if (!$this->isAllowed($m['alias'])) {
                throw new BadRequestException(
                    "Associating {$m['alias']} from {$query->getRepository()->getAlias()} is not allowed by the API"
                );
            }
        }

        return $query;
    }

    /**
     * Returns the current request from controller
     *
     * @return \Cake\Http\ServerRequest
     */
    protected function _getRequest(): ServerRequest
    {
        return $this->getController()->getRequest();
    }

    /**
     * Processses arguments from a descriptor. This allow recursing in a descriptor for deepest parsing
     *
     * @param mixed $nodes Arguments descriptor
     * @param \Cake\ORM\Query $query Support query
     * @param string|null $from Context for parsing arguments
     * @return mixed Processed arguments
     */
    protected function _processArguments($nodes, Query $query, ?string $from = null)
    {
        if (!is_array($nodes)) {
            return $nodes;
        }

        $args = [];

        foreach ($nodes as $key => $node) {
            if (is_int($key)) {
                $args[] = $this->_processArguments($node, $query);
                continue;
            }

            switch ($key) {
                case '()':
                    switch ($from) {
                        case 'contain':
                        case 'innerJoinWith':
                        case 'matching':
                        case 'notMatching':
                        case 'leftJoinWith':
                            if (!is_array($node)) {
                                throw new BadRequestException("Invalid descriptor provided for {$from} closure");
                            }

                            $args[] = function (Query $q) use ($node) {
                                return $this->configure($q, $node);
                            };
                            break;
                        default:
                            if (!is_array($node)) {
                                throw new BadRequestException("Invalid descriptor provided for {$from} closure");
                            }

                            $args[] = function (QueryExpression $e, Query $q) use ($node) {
                                return $this->_processExpression($node, $e, $q);
                            };
                    }
                    break;
                case 'newExpr()':
                    if (!is_array($node)) {
                        throw new BadRequestException("Invalid descriptor provided for {$from}");
                    }

                    $args[] = $this->_processExpression($node, $query->newExpr(), $query);
                    break;
                case 'func()':
                    if (!is_array($node)) {
                        throw new BadRequestException("Invalid descriptor provided for {$from}");
                    }

                    // func is a dead-end, we can return immediatly
                    return $this->_processFunction($node, $query);
                default:
                    $args[$key] = $this->_processArguments($node, $query);
            }
        }

        return $args;
    }

    /**
     * Configure an expression from a descriptor
     *
     * @param array $nodes Descriptor
     * @param \Cake\Database\Expression\QueryExpression $exp Expression to configure
     * @param \Cake\ORM\Query $query Support query
     * @return \Cake\Database\Expression\QueryExpression Configured expression
     */
    protected function _processExpression(array $nodes, QueryExpression $exp, Query $query): QueryExpression
    {
        foreach ($nodes as $key => $node) {
            $key = trim($key, '+');

            if (!method_exists($exp, $key)) {
                throw new BadRequestException("Invalid method {$key} requested on QueryExpression");
            }

            $args = $this->_processArguments($node, $query);
            /** @phpstan-ignore-next-line */
            $exp = call_user_func_array([$exp, $key], $args);
        }

        return $exp;
    }

    /**
     * Processes a Function Builder call from a descriptor
     *
     * @param array $descriptor Descriptor
     * @param \Cake\ORM\Query $query Base query
     * @return \Cake\Database\Expression\FunctionExpression
     */
    protected function _processFunction(array $descriptor, Query $query): FunctionExpression
    {
        $func = $query->func();
        $key = array_keys($descriptor)[0];
        $node = $descriptor[$key];

        if (!method_exists($func, $key)) {
            throw new BadRequestException("Invalid method {$key} requested on FunctionBuilder");
        }

        $args = $this->_processArguments($node, $query);

        /** @phpstan-ignore-next-line */
        return call_user_func_array([$func, $key], $args);
    }
}
