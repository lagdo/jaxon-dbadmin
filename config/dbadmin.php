<?php

use Lagdo\DbAdmin\Admin;
use Lagdo\DbAdmin\Config;
use Lagdo\DbAdmin\Db;
use Lagdo\DbAdmin\Driver;
use Lagdo\DbAdmin\Service;
use Lagdo\DbAdmin\Ui;

use function Jaxon\jaxon;

function getAuth($di): Config\AuthInterface
{
    return $di->h(Config\AuthInterface::class) ?
        $di->g(Config\AuthInterface::class) :
        new class implements Config\AuthInterface {
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
            'path' => __DIR__ . '/../app/ajax/App',
            'namespace' => 'Lagdo\\DbAdmin\\Ajax\\App',
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
            // The database driver used in the application
            Db\AppDriver::class => function($di) {
                // This class will "clone" the selected driver, and define the callbacks.
                // By doing this, the driver classes will call the driver without the callbacks.
                $driver = new Db\AppDriver($di->g(Driver\DriverInterface::class));
                $timer = $di->g(Service\TimerService::class);
                $driver->addQueryCallback(fn() => $timer->stop());
                $logger = $di->g(Service\DbAdmin\QueryLogger::class);
                if ($logger !== null) {
                    $driver->addQueryCallback(fn(string $query) => $logger->saveCommand($query));
                }
                return $driver;
            },
            // Facades to the DB driver features
            Db\Facades\CommandFacade::class => function($di) {
                $dbFacade = $di->g(Db\DbFacade::class);
                $timer = $di->g(Service\TimerService::class);
                $logger = $di->g(Service\DbAdmin\QueryLogger::class);
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
                $logger = $di->g(Service\DbAdmin\QueryLogger::class);
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
            Config\UserFileReader::class => function($di) {
                return new Config\UserFileReader(getAuth($di));
            },
            // Database options for logging
            'dbadmin_logging_database' => function($di) {
                $package = $di->g(Lagdo\DbAdmin\DbAdminPackage::class);
                return !$package->hasLoggingDatabase() ? null :
                    $package->getOption('logging.database');
            },
            // Database driver for logging
            'dbadmin_logging_driver' => function($di) {
                $options = $di->g('dbadmin_logging_database');
                return Driver\Driver::createDriver($options);
            },
            // Query logger
            Service\DbAdmin\QueryLogger::class => function($di) {
                $package = $di->g(Lagdo\DbAdmin\DbAdminPackage::class);
                $options = $package->getOption('logging.options');
                $database = $di->g('dbadmin_logging_database');
                if (!is_array($database) || !is_array($options) ||
                    !$di->h(Config\AuthInterface::class)) {
                    return null;
                }

                // User database, different from the logging database.
                $server = $di->g('dbadmin_config_server');
                $serverOptions = $package->getServerOptions($server);
                $dbFacade = $di->g(Db\DbFacade::class);
                $options['database'] = $dbFacade->getDatabaseOptions($serverOptions);

                $reader = $di->g(Config\UserFileReader::class);
                $database = $reader->getServerOptions($database);
                return new Service\DbAdmin\QueryLogger(getAuth($di),
                    $di->g('dbadmin_logging_driver'), $database, $options);
            },
            // Query history
            Service\DbAdmin\QueryHistory::class => function($di) {
                $package = $di->g(Lagdo\DbAdmin\DbAdminPackage::class);
                $options = $package->getOption('logging.options');
                $database = $di->g('dbadmin_logging_database');
                if (!is_array($database) || !is_array($options) ||
                    !$di->h(Config\AuthInterface::class)) {
                    return null;
                }

                $reader = $di->g(Config\UserFileReader::class);
                $database = $reader->getServerOptions($database);
                return new Service\DbAdmin\QueryHistory(getAuth($di),
                    $di->g('dbadmin_logging_driver'), $database, $options);
            },
            // Query favorites
            Service\DbAdmin\QueryFavorite::class => function($di) {
                $package = $di->g(Lagdo\DbAdmin\DbAdminPackage::class);
                $options = $package->getOption('logging.options');
                $database = $di->g('dbadmin_logging_database');
                if (!is_array($database) || !is_array($options) ||
                    !$di->h(Config\AuthInterface::class)) {
                    return null;
                }

                $reader = $di->g(Config\UserFileReader::class);
                $database = $reader->getServerOptions($database);
                return new Service\DbAdmin\QueryFavorite(getAuth($di),
                    $di->g('dbadmin_logging_driver'), $database, $options);
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
            Ui\Database\ServerUiBuilder::class,
            Ui\Command\QueryUiBuilder::class,
            Ui\Command\LogUiBuilder::class,
            Ui\Command\ImportUiBuilder::class,
            Ui\Command\ExportUiBuilder::class,
            Ui\Table\SelectUiBuilder::class,
            Ui\Table\TableUiBuilder::class,
            Ui\Table\ViewUiBuilder::class,
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
