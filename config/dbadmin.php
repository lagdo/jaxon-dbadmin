<?php

use Jaxon\Di\Container;
use Lagdo\DbAdmin\App;
use Lagdo\DbAdmin\Driver;
use Lagdo\DbAdmin\Support;
use Lagdo\DbAdmin\Support\Config;
use Lagdo\DbAdmin\Support\Driver\DriverHelper;
use Lagdo\DbAdmin\Support\Driver\EngineDecorator;
use Lagdo\DbAdmin\Support\Service;
use Lagdo\Facades\Logger;
use Lagdo\UiBuilder\Builder;
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
        ],
    ],
    'functions' => [
        // We need synchronous calls to this function, so the tabs are created in the correct order.
        'server' => [
            'class' => App\Ajax\Admin\Admin::class,
            'mode' => "'synchronous'",
            'bags' => '["dbadmin","dbadmin.tab"]',
        ],
    ],
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
            Config\ServerConfig::class => function(Container $di) {
                $config = $di->getPackageConfig(App\DbAdminPackage::class);
                $defaultReader = Config\ConfigReader::class;
                $reader = $di->get($config->getOption('config.reader', $defaultReader));

                return new Config\ServerConfig($config, $reader);
            },
            // Options for query recording
            'queries_record_options' => function(Container $di) {
                $serverConfig = $di->g(Config\ServerConfig::class);
                if (!$serverConfig->hasQueryDatabaseOptions()) {
                    Logger::warning('Unable to connect to the audit database: no database connection options provided.');
                    return null;
                }

                return $serverConfig->getQueryRecordOptions();
            },
            // Options for query access
            'queries_admin_options' => function(Container $di) {
                $serverConfig = $di->g(Config\ServerConfig::class);
                if (!$serverConfig->hasQueryDatabaseOptions()) {
                    Logger::warning('Unable to connect to the audit database: no database connection options provided.');
                    return null;
                }

                return $serverConfig->getQueryAdminOptions();
            },
            // Connection to the audit database
            Service\Admin\ConnectionProxy::class => function(Container $di) {
                $auth = $di->g('dbadmin_auth_service');
                $serverConfig = $di->g(Config\ServerConfig::class);
                $database = $serverConfig->getQueryDatabaseOptions();
                $utils = $di->g(Driver\Utils\Utils::class);
                $driver = Driver\Driver::createDriver($utils, $database);

                return new Service\Admin\ConnectionProxy($auth, $driver->engine, $serverConfig);
            },
            // Query logger
            Service\Admin\QueryLogger::class => function(Container $di) {
                if (($options = $di->g('queries_record_options')) === null) {
                    return null;
                }

                /*
                 * The "dbadmin_server_options" entry might not yet be available
                 * in the DI when this class is instantiated. So closures are used
                 * to delay the access to its value until it is actually needed.
                 */
                // User database, different from the audit database.
                $serverOptions = fn() => $di->g('dbadmin_server_options');
                $dbProxy = $di->g(Support\Driver\DriverProxy::class);
                $database = fn() => $dbProxy->getDatabaseOptions($serverOptions());

                $proxy = $di->g(Service\Admin\ConnectionProxy::class);
                return new Service\Admin\QueryLogger($proxy, $options, $database);
            },
            // Query history
            Service\Admin\QueryHistory::class => function(Container $di) {
                if (($options = $di->g('queries_admin_options')) === null) {
                    return null;
                }

                $proxy = $di->g(Service\Admin\ConnectionProxy::class);
                return new Service\Admin\QueryHistory($proxy, $options);
            },
            // Query favorites
            Service\Admin\QueryFavorite::class => function(Container $di) {
                if (($options = $di->g('queries_admin_options')) === null) {
                    return null;
                }

                $proxy = $di->g(Service\Admin\ConnectionProxy::class);
                return new Service\Admin\QueryFavorite($proxy, $options);
            },
            // User preferences
            Service\Admin\Preference::class => function(Container $di) {
                if (($options = $di->g('queries_admin_options')) === null) {
                    return null;
                }

                $proxy = $di->g(Service\Admin\ConnectionProxy::class);
                return new Service\Admin\Preference($proxy, $options);
            },
        ],
        'auto' => [
            ...$container['auto'],
            // Helper for the driver proxies.
            DriverHelper::class,
        ],
        'extend' => [
            ...$container['extend'],
            // Register the UI builder helper for the tab-aware UI components.
            BuilderInterface::class => function(BuilderInterface $builder,
                    Container $di): BuilderInterface {
                $tab = $di->g(App\Ui\Tab\Tab::class);
                $builder->registerHelper('tbn', Builder::TARGET_COMPONENT, $tab->helper(...));
                return $builder;
            },
        ],
    ],
];
