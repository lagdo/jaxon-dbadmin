<?php

use Jaxon\Config\ConfigSetter;
use Jaxon\Di\Container;
use Lagdo\DbAdmin\App;
use Lagdo\DbAdmin\App\Ajax\Admin;
use Lagdo\DbAdmin\Driver;
use Lagdo\DbAdmin\Support;
use Lagdo\DbAdmin\Support\Driver\DI;
use Lagdo\DbAdmin\Support\Driver\EngineDecorator;
use Lagdo\DbAdmin\Support\Provider;
use Lagdo\DbAdmin\Support\Service;
use Lagdo\Facades\Logger;
use Lagdo\UiBuilder\BuilderInterface;

$base = require __DIR__ . '/base.php';
$container = require __DIR__ . '/container.php';

return [
    ...$base,
    'directories' => [
        [
            'path' => dirname(__DIR__) . '/app/Ajax/Admin',
            'namespace' => 'Lagdo\\DbAdmin\\App\\Ajax\\Admin',
            'autoload' => false,
            'classes' => [
                Admin\AppFunc::class => [
                    'functions' => [
                        'start' => [
                            'mode' => "'synchronous'",
                        ],
                        'addSavedTabs' => [
                            'mode' => "'synchronous'",
                        ],
                        'addSavedTab' => [
                            'mode' => "'synchronous'",
                        ],
                    ],
                ],
                Admin\Db\Table\Dml\SearchFunc::class => [
                    'functions' => [
                        'search' => [
                            'mode' => "'synchronous'",
                        ],
                    ],
                ],
            ],
        ],
    ],
    'container' => [
        ...$container,
        'set' => [
            ...$container['set'],
            // The database driver used in the application
            Driver\Driver::class => function(Container $di) {
                $driver = $di->g(DI\ServerDriver::class);

                // Create the Engine decorator, and define the callbacks.
                // The original engine functions, which are called in the driver
                // libraries, will not use the callbacks, while those redefined
                // in the decorator, which are called in the application, will.
                $engine = new EngineDecorator($driver->engine);
                // It's important to add the timer callback before the logger.
                $engine->addQueryCallback($di->g(Service\Admin\QueryTimer::class));
                $engine->addQueryCallback($di->g(Service\Admin\QueryLogger::class));

                return new Driver\Driver($engine, $driver->statement);
            },
            DI\PackageConfig::class => fn(Container $di) =>
                $di->getPackageConfig(App\DbAdminPackage::class),
            // Options for query access
            // Connection to the audit database
            Service\Admin\AuditDatabase::class => function(Container $di) {
                $auth = $di->g(Provider\AuthInterface::class);
                $configProvider = $di->g(Provider\DatabaseConfigProvider::class);
                $database = $configProvider->getQueryDatabaseOptions();
                $utils = $di->g(Driver\Utils\Utils::class);
                $driver = Driver\Driver::createDriver($utils, $database);

                return new Service\Admin\AuditDatabase($auth, $driver->engine, $configProvider);
            },
            // Query logger
            Service\Admin\QueryLogger::class => function(Container $di) {
                $configProvider = $di->g(Provider\DatabaseConfigProvider::class);
                if (!$configProvider->hasQueryDatabaseOptions()) {
                    Logger::warning('Unable to connect to the audit database: no database connection options provided.');
                    return null;
                }

                /*
                 * The "dbadmin_server_options" entry might not yet be available
                 * in the DI when this class is instantiated. So a closure is used
                 * to delay the access to its value until it is actually needed.
                 */
                $database = function() use($di) {
                    // User database, different from the audit database.
                    $currentDb = $di->g(Support\Driver\DriverProxy::class)->currentDb();
                    $options = [];
                    if ($currentDb->name !== '') {
                        $options['database'] = $currentDb->name;
                    }
                    if ($currentDb->schema !== '') {
                        $options['schema'] = $currentDb->schema;
                    }
                    $serverConfig = $di->g(DI\ServerConfig::class);
                    return count($options) === 0 ? $serverConfig :
                        $di->g(ConfigSetter::class)->setOptions($serverConfig, $options);
                };

                $queryTimer = $di->g(Service\Admin\QueryTimer::class);
                $auditDb = $di->g(Service\Admin\AuditDatabase::class);
                return new Service\Admin\QueryLogger($queryTimer,
                    $auditDb, $configProvider, $database);
            },
            // Query history
            Service\Admin\QueryHistory::class => function(Container $di) {
                $configProvider = $di->g(Provider\DatabaseConfigProvider::class);
                if (!$configProvider->hasQueryDatabaseOptions()) {
                    Logger::warning('Unable to connect to the audit database: no database connection options provided.');
                    return null;
                }

                $auditDb = $di->g(Service\Admin\AuditDatabase::class);
                return new Service\Admin\QueryHistory($auditDb, $configProvider);
            },
            // Query favorites
            Service\Admin\QueryFavorite::class => function(Container $di) {
                $configProvider = $di->g(Provider\DatabaseConfigProvider::class);
                if (!$configProvider->hasQueryDatabaseOptions()) {
                    Logger::warning('Unable to connect to the audit database: no database connection options provided.');
                    return null;
                }

                $proxy = $di->g(Service\Admin\AuditDatabase::class);
                return new Service\Admin\QueryFavorite($proxy, $configProvider);
            },
            // User preferences
            Service\Admin\Preference::class => function(Container $di) {
                $configProvider = $di->g(Provider\DatabaseConfigProvider::class);
                if (!$configProvider->hasQueryDatabaseOptions()) {
                    Logger::warning('Unable to connect to the audit database: no database connection options provided.');
                    return null;
                }

                $proxy = $di->g(Service\Admin\AuditDatabase::class);
                return new Service\Admin\Preference($proxy, $configProvider);
            },
        ],
        'extend' => [
            // Register the UI builder helper for the tab-aware UI components.
            BuilderInterface::class => function(BuilderInterface $builder,
                    Container $di): BuilderInterface {
                $tab = $di->g(App\Ui\Tab\Tab::class);
                $builder->registerComponentHelper('tbn', $tab->helper(...));
                return $builder;
            },
        ],
    ],
];
