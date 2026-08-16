<?php

use Jaxon\Di\Container;
use Lagdo\DbAdmin\App;
use Lagdo\DbAdmin\Driver;
use Lagdo\DbAdmin\Support\Driver\DI;
use Lagdo\DbAdmin\Support\Provider;
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
            DI\PackageConfig::class => fn(Container $di) =>
                $di->getPackageConfig(App\DbAuditPackage::class),
            // Connection to the audit database
            Audit\AuditDatabase::class => function(Container $di) {
                $configProvider = $di->g(Provider\DatabaseConfigProvider::class);
                $database = $configProvider->getQueryDatabaseOptions();
                $utils = $di->g(Driver\Utils\Utils::class);
                $driver = Driver\Driver::createDriver($utils, $database);

                return new Audit\AuditDatabase($driver->engine, $configProvider);
            },
            // Query audit
            Audit\QueryLogger::class => function(Container $di) {
                $configProvider = $di->g(Provider\DatabaseConfigProvider::class);
                if (!$configProvider->hasQueryDatabaseOptions()) {
                    return null;
                }

                $auditDb = $di->g(Audit\AuditDatabase::class);
                return new Audit\QueryLogger($auditDb, $configProvider);
            },
        ],
        'alias' => [
            ...$container['alias'],
            // Selected database driver
            Driver\Driver::class => DI\ServerDriver::class,
        ],
    ],
];
