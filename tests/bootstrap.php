<?php
declare(strict_types=1);

use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\Log\Log;
use Cake\Routing\Router;
use Cake\Utility\Security;
use Migrations\TestSuite\Migrator;

/**
 * Test suite bootstrap
 */

$findRoot = function ($root) {
    do {
        $lastRoot = $root;
        $root = dirname($root);
        if (is_dir($root . '/vendor/cakephp/cakephp')) {
            return $root;
        }
    } while ($root !== $lastRoot);
    throw new Exception('Cannot find the root of the application, unable to run tests');
};
$root = $findRoot(__FILE__);
unset($findRoot);
chdir($root);

require_once 'vendor/cakephp/cakephp/src/basics.php';
require_once 'vendor/autoload.php';

define('ROOT', $root . DS . 'tests' . DS . 'test_app' . DS);
define('APP', ROOT . 'App' . DS);
define('TMP', sys_get_temp_dir() . DS);
// Alter config folder
define('CONFIG', __DIR__ . DS . 'config' . DS);

Configure::write('debug', true);
Configure::write('App', [
    'namespace' => 'TestApp',
    'paths' => [
        'plugins' => [ROOT . 'Plugin' . DS],
        'templates' => [ROOT . 'templates' . DS],
    ],
]);

// Build DSN for local/CI testing - SQLite in memory is fine
$dsn = 'sqlite:///:memory:';

// Create test connection
ConnectionManager::setConfig('test', [
  'url' => $dsn,
  'log' => false,
]);

// Console logging
Log::setConfig('queries', [
    'className' => 'Console',
    'stream' => 'php://stderr',
    'scopes' => ['queriesLog'],
]);

Router::reload();
Security::setSalt('YJfIxfs2guVoUubWDYhfk3b0qyJfIxfs2guwv6iR2G0FgaC9mj');

$_SERVER['PHP_SELF'] = '/';

// Run migrations
$migrator = new Migrator();
$migrator->run();
