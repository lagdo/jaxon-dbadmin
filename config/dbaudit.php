<?php

use Jaxon\Config\ConfigSetter;
use Jaxon\Di\Container;
use Lagdo\DbAdmin\App;
use Lagdo\DbAdmin\Driver;
use Lagdo\DbAdmin\Support\Config;
use Lagdo\DbAdmin\Support\Service\Audit;

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
                $defaultReader = Config\ConfigReader::class;
                $reader = $di->get($config->getOption('config.reader', $defaultReader));
                // Move the options under the "queries" key. Needed by the ServerConfig class.
                $config = (new ConfigSetter())->newConfig([
                    'database' => $config->getOption('database'),
                    'audit' => $config->getOption('audit', []),
                ], 'queries');

                return new Config\ServerConfig($config, $reader);
            },
            // Connection to the audit database
            Audit\ConnectionProxy::class => function(Container $di) {
                $serverConfig = $di->g(Config\ServerConfig::class);
                $database = $serverConfig->getQueryDatabaseOptions();
                $utils = $di->g(Driver\Utils\Utils::class);
                $driver = Driver\Driver::createDriver($utils, $database);

                return new Audit\ConnectionProxy($driver->engine, $serverConfig);
            },
            // Query audit
            Audit\QueryLogger::class => function(Container $di) {
                $serverConfig = $di->g(Config\ServerConfig::class);
                $database = $serverConfig->getQueryDatabaseOptions();
                if ($database === null) {
                    return null;
                }

                $options = $serverConfig->getQueryAuditOptions();
                $proxy = $di->g(Audit\ConnectionProxy::class);
                return new Audit\QueryLogger($proxy, $options);
            },
        ],
        'alias' => [
            ...$container['alias'],
            // Selected database driver
            Driver\Driver::class => 'dbadmin_server_driver',
        ],
    ],
];
