<?php

use Lagdo\DbAdmin\Admin;
use Lagdo\DbAdmin\Config;
use Lagdo\DbAdmin\Db;
use Lagdo\DbAdmin\Driver;
use Lagdo\DbAdmin\Service;
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
    'metadata' => 'annotations',
    'directories' => [
        [
            'path' => __DIR__ . '/../app/ajax/Log',
            'namespace' => 'Lagdo\\DbAdmin\\Ajax\\Log',
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
                $package = $di->g(Lagdo\DbAdmin\DbAdminPackage::class);
                foreach($package->getServers() as $server => $options) {
                    $di->set("dbadmin_driver_$server", fn() =>
                        Driver\Driver::createDriver($options));
                }

                $server = $di->g('dbadmin_config_server');
                return $di->g("dbadmin_driver_$server");
            },
            // Facades to the DB driver features
            Db\Facades\CommandFacade::class => function($di) {
                $dbFacade = $di->g(Db\DbFacade::class);
                $timer = $di->g(Service\TimerService::class);
                $logger = $di->g(Service\LogWriter::class);
                return new Db\Facades\CommandFacade($dbFacade, $timer, $logger);
            },
            Db\Facades\DatabaseFacade::class => function($di) {
                $dbFacade = $di->g(Db\DbFacade::class);
                $server = $di->g('dbadmin_config_server');
                $package = $di->g(Lagdo\DbAdmin\DbAdminPackage::class);
                $options = $package->getServerOptions($server);
                return new Db\Facades\DatabaseFacade($dbFacade, $options);
            },
            Db\Facades\ExportFacade::class => function($di) {
                $dbFacade = $di->g(Db\DbFacade::class);
                return new Db\Facades\ExportFacade($dbFacade);
            },
            Db\Facades\ImportFacade::class => function($di) {
                $dbFacade = $di->g(Db\DbFacade::class);
                $timer = $di->g(Service\TimerService::class);
                $logger = $di->g(Service\LogWriter::class);
                return new Db\Facades\ImportFacade($dbFacade, $timer, $logger);
            },
            Db\Facades\QueryFacade::class => function($di) {
                $dbFacade = $di->g(Db\DbFacade::class);
                return new Db\Facades\QueryFacade($dbFacade);
            },
            Db\Facades\SelectFacade::class => function($di) {
                $dbFacade = $di->g(Db\DbFacade::class);
                $timer = $di->g(Service\TimerService::class);
                return new Db\Facades\SelectFacade($dbFacade, $timer);
            },
            Db\Facades\ServerFacade::class => function($di) {
                $dbFacade = $di->g(Db\DbFacade::class);
                $server = $di->g('dbadmin_config_server');
                $package = $di->g(Lagdo\DbAdmin\DbAdminPackage::class);
                $options = $package->getServerOptions($server);
                return new Db\Facades\ServerFacade($dbFacade, $options);
            },
            Db\Facades\TableFacade::class => function($di) {
                $dbFacade = $di->g(Db\DbFacade::class);
                return new Db\Facades\TableFacade($dbFacade);
            },
            Db\Facades\UserFacade::class => function($di) {
                $dbFacade = $di->g(Db\DbFacade::class);
                return new Db\Facades\UserFacade($dbFacade);
            },
            Db\Facades\ViewFacade::class => function($di) {
                $dbFacade = $di->g(Db\DbFacade::class);
                return new Db\Facades\ViewFacade($dbFacade);
            },
            Config\UserFileReader::class => function() {
                return new Config\UserFileReader(getAuth());
            },
            // Query logging
            Service\LogReader::class => function($di) {
                $package = $di->g(Lagdo\DbAdmin\LoggingPackage::class);
                $database = $package->getOption('database');
                $driverId = 'dbadmin_driver_' . ($database['driver'] ?? '');
                if (!$di->h($driverId) || !is_array($database)) {
                    return null;
                }

                $reader = $di->g(Config\UserFileReader::class);
                $db = $di->g(Db\DbFacade::class);
                $limit = 15;
                return new Service\LogReader($db, $di->g($driverId),
                    $limit, $reader->getServerOptions($database));
            },
        ],
        'auto' => [
            // The translator
            Lagdo\DbAdmin\Translator::class,
            // The string manipulation class
            Driver\Utils\Str::class,
            // The user input
            Driver\Utils\Input::class,
            // The utils class
            Driver\Utils\Utils::class,
            // The facade to the database features
            Db\DbFacade::class,
            // The Timer service
            Service\TimerService::class,
            // The db classes
            Admin\Admin::class,
            // The UI builders
            Ui\UiBuilder::class,
            Ui\InputBuilder::class,
            Ui\MenuBuilder::class,
            Ui\Log\LogUiBuilder::class,
        ],
        'alias' => [
            // The translator
            Driver\Utils\TranslatorInterface::class => Lagdo\DbAdmin\Translator::class,
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
