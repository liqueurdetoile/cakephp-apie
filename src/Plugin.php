<?php
declare(strict_types=1);

namespace Lqdt\CakephpApie;

use Cake\Core\BasePlugin;

/**
 * Plugin class for CakePHP.
 */
class Plugin extends BasePlugin
{
    /**
     * Do bootstrapping or not
     *
     * @var bool
     */
    protected $bootstrapEnabled = false;

    /**
     * Load routes or not
     *
     * @var bool
     */
    protected $routesEnabled = false;

    /**
     * Console middleware
     *
     * @var bool
     */
    protected $consoleEnabled = false;
}
