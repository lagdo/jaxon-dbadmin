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
            'server_config_provider_options' => function(Container $di) {
                $config = $di->getPackageConfig(App\DbAuditPackage::class);
                // Move the "database" and "audit" options under the "queries" key.
                // Needed by the ConfigProvider class.
                return (new ConfigSetter())->newConfig([
                    'reader' => $config->getOption('reader', []),
                    'queries' => [
                        'database' => $config->getOption('database'),
                        'audit' => $config->getOption('audit', []),
                    ],
                ]);
            },
            // Connection to the audit database
            Audit\ConnectionProxy::class => function(Container $di) {
                $configProvider = $di->g(Config\DatabaseConfigProvider::class);
                $database = $configProvider->getQueryDatabaseOptions();
                $utils = $di->g(Driver\Utils\Utils::class);
                $driver = Driver\Driver::createDriver($utils, $database);

                return new Audit\ConnectionProxy($driver->engine, $configProvider);
            },
            // Query audit
            Audit\QueryLogger::class => function(Container $di) {
                $configProvider = $di->g(Config\DatabaseConfigProvider::class);
                if (!$configProvider->hasQueryDatabaseOptions()) {
                    return null;
                }

                $options = $configProvider->getQueryAuditOptions();
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
