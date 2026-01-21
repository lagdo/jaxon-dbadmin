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
                // Move the options under the "audit" key. Needed by the ServerConfig class.
                $config = (new ConfigSetter())->newConfig(['audit' => $config->getValues()]);
                return new Config\ServerConfig($config);
            },
            // Connection to the audit database
            Service\Audit\ConnectionProxy::class => function(Container $di) {
                $serverConfig = $di->g(Config\ServerConfig::class);
                $database = $serverConfig->getAuditDatabase();
                $driver = Db\Driver\AppDriver::createDriver($database);
                return new Service\Audit\ConnectionProxy($driver, $database);
            },
            // Query audit
            Service\Audit\QueryLogger::class => function(Container $di) {
                $config = $di->getPackageConfig(Db\DbAuditPackage::class);
                $database = $config->getOption('database');
                $options = $config->getOption('options', []);
                if (!is_array($database) || !is_array($options)) {
                    return null;
                }

                $proxy = $di->g(Service\Audit\ConnectionProxy::class);
                return new Service\Audit\QueryLogger($proxy, $options);
            },
        ],
    ],
];
