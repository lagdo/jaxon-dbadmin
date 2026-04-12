<?php

use Jaxon\Config\ConfigSetter;
use Jaxon\Di\Container;
use Lagdo\DbAdmin\Db;
use Lagdo\DbAdmin\Db\Config;
use Lagdo\DbAdmin\Db\Service;
use Lagdo\DbAdmin\Driver;

$base = require __DIR__ . '/base.php';
$container = require __DIR__ . '/container.php';

return [
    ...$base,
    'directories' => [
        [
            'path' => __DIR__ . '/../app/ajax/Audit',
            'namespace' => 'Lagdo\\DbAdmin\\Ajax\\Audit',
            'autoload' => false,
        ],
    ],
    'container' => [
        ...$container,
        'set' => [
            ...$container['set'],
            Config\ServerConfig::class => function(Container $di) {
                $config = $di->getPackageConfig(Db\DbAuditPackage::class);
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
    ],
];
