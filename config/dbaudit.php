<?php

use Jaxon\Config\ConfigSetter;
use Jaxon\Di\Container;
use Lagdo\DbAdmin\App;
use Lagdo\DbAdmin\Driver;
use Lagdo\DbAdmin\Support\Config;
use Lagdo\DbAdmin\Support\Service;

$base = require __DIR__ . '/base.php';
$container = require __DIR__ . '/container.php';

return [
    ...$base,
    'directories' => [
        [
            'path' => dirname(__DIR__) . '/app/Ajax/Audit',
            'namespace' => 'Lagdo\\DbAdmin\\App\\Ajax\\Audit',
            'autoload' => false,
        ],
    ],
    'container' => [
        ...$container,
        'set' => [
            ...$container['set'],
            Config\ServerConfig::class => function(Container $di) {
                $config = $di->getPackageConfig(App\DbAuditPackage::class);
                $reader = $di->get($config->getOption('config.reader',
                    Config\ConfigReader::class));
                // Move the options under the "queries" key. Needed by the ServerConfig class.
                $config = (new ConfigSetter())->newConfig([
                    'database' => $config->getOption('database'),
                    'audit' => $config->getOption('audit', []),
                ], 'queries');
                $authSetup = $di->has(Config\AuthInterface::class);
                return new Config\ServerConfig($config, $reader, $authSetup);
            },
            // Connection to the audit database
            Service\Audit\ConnectionProxy::class => function(Container $di) {
                $serverConfig = $di->g(Config\ServerConfig::class);
                $database = $serverConfig->getQueryDatabaseOptions();
                $driver = Driver\Driver::createDriver($di->g(Driver\Utils\Utils::class), $database);
                return new Service\Audit\ConnectionProxy($driver->engine, $database);
            },
            // Query audit
            Service\Audit\QueryLogger::class => function(Container $di) {
                $serverConfig = $di->g(Config\ServerConfig::class);
                $database = $serverConfig->getQueryDatabaseOptions();
                if ($database === null) {
                    return null;
                }

                $options = $serverConfig->getQueryAuditOptions();
                $proxy = $di->g(Service\Audit\ConnectionProxy::class);
                return new Service\Audit\QueryLogger($proxy, $options);
            },
        ],
        'alias' => [
            ...$container['alias'],
            // Selected database driver
            Driver\Driver::class => 'dbadmin_server_driver',
        ],
    ],
];
