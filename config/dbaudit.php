<?php

use Jaxon\Config\ConfigSetter;
use Jaxon\Di\Container;
use Lagdo\DbAdmin\Db;
use Lagdo\DbAdmin\Db\Config;
use Lagdo\DbAdmin\Db\Service;

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
                $reader = $di->g($config->getOption('config.reader',
                    Config\ConfigReader::class));
                // Move the options under the "audit" key. Needed by the ServerConfig class.
                $config = (new ConfigSetter())->newConfig([
                    'database' => $config->getOption('database'),
                    'options' => $config->getOption('options', []),
                ], 'audit');
                return new Config\ServerConfig($config, $reader);
            },
            // Connection to the audit database
            Service\Audit\ConnectionProxy::class => function(Container $di) {
                /** @var Config\ServerConfig */
                $serverConfig = $di->g(Config\ServerConfig::class);
                $database = $serverConfig->getAuditDatabase();
                $driver = Db\Driver\AppDriver::createDriver($di, $database);
                return new Service\Audit\ConnectionProxy($driver, $database);
            },
            // Query audit
            Service\Audit\QueryLogger::class => function(Container $di) {
                /** @var Config\ServerConfig */
                $serverConfig = $di->g(Config\ServerConfig::class);
                $database = $serverConfig->getAuditDatabase();
                if ($database === null) {
                    return null;
                }

                $options = $serverConfig->getAuditOptions();
                $proxy = $di->g(Service\Audit\ConnectionProxy::class);
                return new Service\Audit\QueryLogger($proxy, $options);
            },
        ],
    ],
];
