<?php

use Jaxon\Di\Container;
use Lagdo\DbAdmin\Db;
use Lagdo\DbAdmin\Db\Config;
use Lagdo\DbAdmin\Db\Service;
use Lagdo\DbAdmin\Driver;
use Lagdo\DbAdmin\Ui;
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
                $reader = $di->g($config->getOption('config.reader',
                    Config\ConfigReader::class));
                return new Config\ServerConfig($config, $reader);
            },
            // The database driver used in the application
            Db\Driver\AppDriver::class => function(Container $di) {
                // This class will "clone" the selected driver, and define the callbacks.
                // By doing this, the driver classes will call the driver without the callbacks.
                $driver = new Db\Driver\AppDriver($di->g(Driver\DriverInterface::class));
                $timerCallback = function() use($di) {
                    $timer = $di->g(Service\TimerService::class);
                    $timer->stop();
                };
                $loggerCallback = function() use($di, $driver) {
                    $logger = $di->g(Service\Admin\QueryLogger::class);
                    if ($logger !== null) {
                        $driver->addQueryCallback($logger->saveCommand(...));
                    }
                };
                $driver->addQueryCallback($timerCallback);
                $driver->addQueryCallback($loggerCallback);
                return $driver;
            },
            // Database options for audit
            'dbaudit_database_options' => function($di) {
                $serverConfig = $di->g(Config\ServerConfig::class);
                $database = $serverConfig->getAuditDatabase();
                $options = $serverConfig->getAuditOptions();
                return is_array($database) &&
                    $di->h(Config\AuthInterface::class) ? $options : null;
            },
            // Connection to the audit database
            Service\Admin\ConnectionProxy::class => function(Container $di) {
                $auth = $di->g('dbadmin_auth_service');
                $serverConfig = $di->g(Config\ServerConfig::class);
                $database = $serverConfig->getAuditDatabase();
                $driver = Db\Driver\AppDriver::createDriver($di, $database);
                return new Service\Admin\ConnectionProxy($auth, $driver, $database);
            },
            // Query logger
            Service\Admin\QueryLogger::class => function(Container $di) {
                if (($options = $di->g('dbaudit_database_options')) === null) {
                    return null;
                }

                // User database, different from the audit database.
                $serverOptions = $di->g('dbadmin_server_options');
                $dbFacade = $di->g(Db\Driver\DbFacade::class);
                $options['database'] = $dbFacade->getDatabaseOptions($serverOptions);

                $proxy = $di->g(Service\Admin\ConnectionProxy::class);
                return new Service\Admin\QueryLogger($proxy, $options);
            },
            // Query history
            Service\Admin\QueryHistory::class => function(Container $di) {
                if (($options = $di->g('dbaudit_database_options')) === null) {
                    return null;
                }

                $proxy = $di->g(Service\Admin\ConnectionProxy::class);
                return new Service\Admin\QueryHistory($proxy, $options);
            },
            // Query favorites
            Service\Admin\QueryFavorite::class => function(Container $di) {
                if (($options = $di->g('dbaudit_database_options')) === null) {
                    return null;
                }

                $proxy = $di->g(Service\Admin\ConnectionProxy::class);
                return new Service\Admin\QueryFavorite($proxy, $options);
            },
        ],
        'extend' => [
            BuilderInterface::class => function(BuilderInterface $builder): BuilderInterface {
                $builder->registerHelper('tbn', Builder::TARGET_COMPONENT, Ui\Tab::helper(...));
                return $builder;
            },
        ],
    ],
];
