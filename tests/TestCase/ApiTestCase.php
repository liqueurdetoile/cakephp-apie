<?php
declare(strict_types=1);

namespace Lqdt\CakephpApie\Test\TestCase;

use Cake\Http\ServerRequest;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\Fixture\FixtureStrategyInterface;
use Cake\TestSuite\TestCase;
use CakephpFixtureFactories\ORM\FactoryTableRegistry;
use CakephpTestSuiteLight\Fixture\TriggerStrategy;
use Lqdt\CakephpApie\Test\Model\Table\AgentsTable;
use Lqdt\CakephpApie\Test\Model\Table\ClientsTable;

class ApiTestCase extends TestCase
{
    protected function getFixtureStrategy(): FixtureStrategyInterface
    {
        return new TriggerStrategy();
    }

    public function setUp(): void
    {
        parent::setUp();

        // Register test models
        TableRegistry::getTableLocator()->set('Clients', new ClientsTable());
        FactoryTableRegistry::getTableLocator()->set('Clients', new ClientsTable());
        TableRegistry::getTableLocator()->set('Agents', new AgentsTable());
        FactoryTableRegistry::getTableLocator()->set('Agents', new AgentsTable());
    }

    public function getRequest(string $url, string $method = 'GET'): ServerRequest
    {
        return new ServerRequest([
            'url' => $url,
            'environment' => [
                'REQUEST_METHOD' => $method,
            ],
        ]);
    }

    public function getQueryParam(array $q): string
    {
        return urlencode((string)json_encode($q));
    }

    public function getQueryRequest(array $q): ServerRequest
    {
        return $this->getRequest('/?q=' . $this->getQueryParam($q));
    }

    public function getController(ServerRequest $request, array $options = []): ApiController
    {
        $controller = new ApiController($request);
        $controller->loadComponent('Lqdt/CakephpApie.Api', $options);

        return $controller;
    }

    public function getQueryController(array $q, array $options = []): ApiController
    {
        $request = $this->getQueryRequest($q);

        return $this->getController($request, $options);
    }
}
