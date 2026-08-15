<?php

use Jaxon\Di\Container;
use Lagdo\DbAdmin\App;
use Lagdo\DbAdmin\App\Ajax\Admin;
use Lagdo\DbAdmin\Driver;
use Lagdo\DbAdmin\Support;
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
    // 'functions' => [
    //     // We need synchronous calls to this function, so the tabs are created in the correct order.
    //     'addTab' => [
    //         'class' => App\Ajax\Admin\AppFunc::class,
    //         'mode' => "'synchronous'",
    //         'bags' => '["dbadmin","dbadmin.tab"]',
    //     ],
    // ],
    'container' => [
        ...$container,
        'set' => [
            ...$container['set'],
            // The database driver used in the application
            Driver\Driver::class => function(Container $di) {
                /** @var Driver\Driver */
                $driver = $di->g('dbadmin_server_driver');

                // Create the Engine decorator, and define the callbacks.
                // The original engine functions, which are called in the driver
                // libraries, will not use the callbacks, while those redefined
                // in the decorator, which are called in the application, will.
                $engine = new EngineDecorator($driver->engine);
                $timerCallback = function() use($di) {
                    $timer = $di->g(Service\TimerService::class);
                    $timer->stop();
                };
                $loggerCallback = function(string $query) use($di) {
                    $logger = $di->g(Service\Admin\QueryLogger::class);
                    if ($logger !== null) {
                        $logger->saveCommand($query);
                    }
                };
                $engine->addQueryCallback($timerCallback);
                $engine->addQueryCallback($loggerCallback);

                return new Driver\Driver($engine, $driver->statement);
            },
            'dbapp_package_config' => fn(Container $di) =>
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

                $options = $configProvider->getQueryDatabaseOptions();
                /*
                 * The "dbadmin_server_options" entry might not yet be available
                 * in the DI when this class is instantiated. So a closure is used
                 * to delay the access to its value until it is actually needed.
                 */
                $database = function() use($di) {
                    // User database, different from the audit database.
                    $dbProxy = $di->g(Support\Driver\DriverProxy::class);
                    /** @var array */
                    $serverOptions = $di->g('dbadmin_server_options');
                    return $dbProxy->getDatabaseOptions($serverOptions);
                };

                $auditDb = $di->g(Service\Admin\AuditDatabase::class);
                return new Service\Admin\QueryLogger($auditDb, $options, $database);
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
