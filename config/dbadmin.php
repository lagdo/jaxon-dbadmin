<?php

use Jaxon\Di\Container;
use Lagdo\DbAdmin\Db;
use Lagdo\DbAdmin\Db\Config;
use Lagdo\DbAdmin\Db\Service;
use Lagdo\DbAdmin\Support;
use Lagdo\DbAdmin\Ui;
use Lagdo\Facades\Logger;
use Lagdo\UiBuilder\Builder;
use Lagdo\UiBuilder\BuilderInterface;

$base = require __DIR__ . '/base.php';
$container = require __DIR__ . '/container.php';

return [
    ...$base,
    'directories' => [
        [
            'path' => __DIR__ . '/../app/ajax/Admin',
            'namespace' => 'Lagdo\\DbAdmin\\Ajax\\Admin',
            'autoload' => false,
        ],
    ],
    'container' => [
        ...$container,
        'set' => [
            ...$container['set'],
            Config\ServerConfig::class => function(Container $di) {
                $config = $di->getPackageConfig(Db\DbAdminPackage::class);
                $reader = $di->get($config->getOption('config.reader',
                    Config\ConfigReader::class));
                $authSetup = $di->has(Config\AuthInterface::class);
                return new Config\ServerConfig($config, $reader, $authSetup);
            },
            // The database driver used in the application
            Db\Driver\AppDriver::class => function(Container $di) {
                // This class will "clone" the selected driver, and define the callbacks.
                // By doing this, the driver classes will call the driver without the callbacks.
                $driver = new Db\Driver\AppDriver($di->g(Support\DriverInterface::class));
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
                $driver->addQueryCallback($timerCallback);
                $driver->addQueryCallback($loggerCallback);
                return $driver;
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
                $driver = Db\Driver\AppDriver::createDriver($di, $database);
                return new Service\Admin\ConnectionProxy($auth, $driver, $database);
            },
            // Query logger
            Service\Admin\QueryLogger::class => function(Container $di) {
                if (($options = $di->g('queries_record_options')) === null) {
                    return null;
                }

                // User database, different from the audit database.
                $serverOptions = $di->g('dbadmin_server_options');
                $dbProxy = $di->g(Db\Driver\DbProxy::class);
                $database = $dbProxy->getDatabaseOptions($serverOptions);

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
        'extend' => [
            BuilderInterface::class => function(BuilderInterface $builder): BuilderInterface {
                $builder->registerHelper('tbn', Builder::TARGET_COMPONENT,
                    Ui\TabApp::helper(...));
                return $builder;
            },
        ],
    ],
];
