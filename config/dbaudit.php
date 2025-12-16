<?php

use Lagdo\DbAdmin\Db;
use Lagdo\DbAdmin\Db\Config;
use Lagdo\DbAdmin\Db\Driver\Facades;
use Lagdo\DbAdmin\Db\Service;
use Lagdo\DbAdmin\Driver;
use Lagdo\DbAdmin\Ui;

use function Jaxon\jaxon;

function getAuth(): Config\AuthInterface
{
    return new class implements Config\AuthInterface {
        public function user(): string
        {
            return '';
        }
        public function role(): string
        {
            return '';
        }
    };
}

return [
    'metadata' => [
        'format' => 'attributes',
    ],
    'directories' => [
        [
            'path' => __DIR__ . '/../app/ajax/Audit',
            'namespace' => 'Lagdo\\DbAdmin\\Ajax\\Audit',
            'autoload' => false,
        ],
    ],
    'views' => [
        'dbadmin::codes' => [
            'directory' => __DIR__ . '/../templates/codes',
            'extension' => '',
            'renderer' => 'jaxon',
        ],
        'dbadmin::views' => [
            'directory' => __DIR__ . '/../templates/views',
            'extension' => '.php',
            'renderer' => 'jaxon',
        ],
        'dbadmin::templates' => [
            'directory' => __DIR__ . '/../templates/views',
            'extension' => '.php',
            'renderer' => 'jaxon',
        ],
        'pagination' => [
            'directory' => __DIR__ . '/../templates/pagination',
            'extension' => '.php',
            'renderer' => 'jaxon',
        ],
    ],
    'container' => [
        'set' => [
            // Selected database driver
            Driver\DriverInterface::class => function($di) {
                // Register a driver for each database server.
                $package = $di->g(Db\DbAdminPackage::class);
                foreach($package->getServers() as $server => $options) {
                    $di->set("dbadmin_driver_$server", fn() =>
                        Db\Driver\AppDriver::createDriver($options));
                }

                $server = $di->g('dbadmin_config_server');
                return $di->g("dbadmin_driver_$server");
            },
            // Facades to the DB driver features
            Facades\CommandFacade::class => function($di) {
                $dbFacade = $di->g(Db\Driver\DbFacade::class);
                $timer = $di->g(Service\TimerService::class);
                $logger = $di->g(Service\Admin\QueryLogger::class);
                return new Facades\CommandFacade($dbFacade, $timer, $logger);
            },
            Facades\DatabaseFacade::class => function($di) {
                $dbFacade = $di->g(Db\Driver\DbFacade::class);
                $server = $di->g('dbadmin_config_server');
                $package = $di->g(Db\DbAdminPackage::class);
                $options = $package->getServerOptions($server);
                return new Facades\DatabaseFacade($dbFacade, $options);
            },
            Facades\ExportFacade::class => function($di) {
                $dbFacade = $di->g(Db\Driver\DbFacade::class);
                return new Facades\ExportFacade($dbFacade);
            },
            Facades\ImportFacade::class => function($di) {
                $dbFacade = $di->g(Db\Driver\DbFacade::class);
                $timer = $di->g(Service\TimerService::class);
                $logger = $di->g(Service\Admin\QueryLogger::class);
                return new Facades\ImportFacade($dbFacade, $timer, $logger);
            },
            Facades\QueryFacade::class => function($di) {
                $dbFacade = $di->g(Db\Driver\DbFacade::class);
                return new Facades\QueryFacade($dbFacade);
            },
            Facades\SelectFacade::class => function($di) {
                $dbFacade = $di->g(Db\Driver\DbFacade::class);
                $timer = $di->g(Service\TimerService::class);
                return new Facades\SelectFacade($dbFacade, $timer);
            },
            Facades\ServerFacade::class => function($di) {
                $dbFacade = $di->g(Db\Driver\DbFacade::class);
                $server = $di->g('dbadmin_config_server');
                $package = $di->g(Db\DbAdminPackage::class);
                $options = $package->getServerOptions($server);
                return new Facades\ServerFacade($dbFacade, $options);
            },
            Facades\TableFacade::class => function($di) {
                $dbFacade = $di->g(Db\Driver\DbFacade::class);
                return new Facades\TableFacade($dbFacade);
            },
            Facades\UserFacade::class => function($di) {
                $dbFacade = $di->g(Db\Driver\DbFacade::class);
                return new Facades\UserFacade($dbFacade);
            },
            Facades\ViewFacade::class => function($di) {
                $dbFacade = $di->g(Db\Driver\DbFacade::class);
                return new Facades\ViewFacade($dbFacade);
            },
            Config\UserFileReader::class => function() {
                return new Config\UserFileReader(getAuth());
            },
            // Query audit
            Service\Audit\QueryLogger::class => function($di) {
                $package = $di->g(Db\DbAuditPackage::class);
                $database = $package->getOption('database');
                $options = $package->getOption('options', []);
                if (!is_array($database) || !is_array($options)) {
                    return null;
                }

                $driver = Db\Driver\AppDriver::createDriver($database);
                $reader = $di->g(Config\UserFileReader::class);
                $db = $di->g(Db\Driver\DbFacade::class);
                return new Service\Audit\QueryLogger($db, $driver,
                    $reader->getServerOptions($database), $options);
            },
        ],
        'auto' => [
            // The translator
            Lagdo\DbAdmin\Db\Translator::class,
            // The string manipulation class
            Driver\Utils\Str::class,
            // The user input
            Driver\Utils\Input::class,
            // The utils class
            Driver\Utils\Utils::class,
            // The db classes
            Db\Driver\AppPage::class,
            // The facade to the database features
            Db\Driver\DbFacade::class,
            // The Timer service
            Service\TimerService::class,
            // The UI builders
            Ui\UiBuilder::class,
            Ui\InputBuilder::class,
            Ui\MenuBuilder::class,
            Ui\AuditUiBuilder::class,
        ],
        'alias' => [
            // The translator
            Driver\Utils\TranslatorInterface::class => Lagdo\DbAdmin\Db\Translator::class,
        ],
    ],
    'exceptions' => [
        Db\Exception\DbException::class => function(Db\Exception\DbException $dbException) {
            $response = jaxon()->getResponse();
            $response->dialog->warning($dbException->getMessage());
            return $response;
        },
    ],
];
