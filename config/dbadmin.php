<?php

use Lagdo\DbAdmin\Db;
use Lagdo\DbAdmin\Db\Config;
use Lagdo\DbAdmin\Db\Driver\Facades;
use Lagdo\DbAdmin\Db\Service;
use Lagdo\DbAdmin\Driver;
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
                $package = $di->g(Db\DbAdminPackage::class);
                foreach($package->getServers() as $server => $options) {
                    $di->set("dbadmin_driver_$server", fn() =>
                        Db\Driver\AppDriver::createDriver($options));
                }

                $server = $di->g('dbadmin_config_server');
                return $di->g("dbadmin_driver_$server");
            },
            // The database driver used in the application
            Db\Driver\AppDriver::class => function($di) {
                // This class will "clone" the selected driver, and define the callbacks.
                // By doing this, the driver classes will call the driver without the callbacks.
                $driver = new Db\Driver\AppDriver($di->g(Driver\DriverInterface::class));
                $timer = $di->g(Service\TimerService::class);
                $driver->addQueryCallback(fn() => $timer->stop());
                $logger = $di->g(Service\DbAdmin\QueryLogger::class);
                if ($logger !== null) {
                    $driver->addQueryCallback(fn(string $query) => $logger->saveCommand($query));
                }
                return $driver;
            },
            // Facades to the DB driver features
            Facades\CommandFacade::class => function($di) {
                $dbFacade = $di->g(Db\Driver\DbFacade::class);
                $timer = $di->g(Service\TimerService::class);
                $logger = $di->g(Service\DbAdmin\QueryLogger::class);
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
                $logger = $di->g(Service\DbAdmin\QueryLogger::class);
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
            Config\UserFileReader::class => function($di) {
                return new Config\UserFileReader(getAuth($di));
            },
            // Database options for audit
            'dbaudit_database_server' => function($di) {
                $package = $di->g(Db\DbAdminPackage::class);
                return !$package->hasAuditDatabase() ? null :
                    $package->getOption('audit.database');
            },
            // Database driver for audit
            'dbaudit_database_driver' => function($di) {
                $options = $di->g('dbaudit_database_server');
                return Db\Driver\AppDriver::createDriver($options);
            },
            // Query logger
            Service\DbAdmin\QueryLogger::class => function($di) {
                $package = $di->g(Db\DbAdminPackage::class);
                $options = $package->getOption('audit.options');
                $database = $di->g('dbaudit_database_server');
                if (!is_array($database) || !is_array($options) ||
                    !$di->h(Config\AuthInterface::class)) {
                    return null;
                }

                // User database, different from the audit database.
                $server = $di->g('dbadmin_config_server');
                $serverOptions = $package->getServerOptions($server);
                $dbFacade = $di->g(Db\Driver\DbFacade::class);
                $options['database'] = $dbFacade->getDatabaseOptions($serverOptions);

                $reader = $di->g(Config\UserFileReader::class);
                $database = $reader->getServerOptions($database);
                return new Service\DbAdmin\QueryLogger(getAuth($di),
                    $di->g('dbaudit_database_driver'), $database, $options);
            },
            // Query history
            Service\DbAdmin\QueryHistory::class => function($di) {
                $package = $di->g(Db\DbAdminPackage::class);
                $options = $package->getOption('audit.options');
                $database = $di->g('dbaudit_database_server');
                if (!is_array($database) || !is_array($options) ||
                    !$di->h(Config\AuthInterface::class)) {
                    return null;
                }

                $reader = $di->g(Config\UserFileReader::class);
                $database = $reader->getServerOptions($database);
                return new Service\DbAdmin\QueryHistory(getAuth($di),
                    $di->g('dbaudit_database_driver'), $database, $options);
            },
            // Query favorites
            Service\DbAdmin\QueryFavorite::class => function($di) {
                $package = $di->g(Db\DbAdminPackage::class);
                $options = $package->getOption('audit.options');
                $database = $di->g('dbaudit_database_server');
                if (!is_array($database) || !is_array($options) ||
                    !$di->h(Config\AuthInterface::class)) {
                    return null;
                }

                $reader = $di->g(Config\UserFileReader::class);
                $database = $reader->getServerOptions($database);
                return new Service\DbAdmin\QueryFavorite(getAuth($di),
                    $di->g('dbaudit_database_driver'), $database, $options);
            },
        ],
        'auto' => [
            // The translator
            Db\Translator::class,
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
            Ui\Database\ServerUiBuilder::class,
            Ui\Command\QueryUiBuilder::class,
            Ui\Command\AuditUiBuilder::class,
            Ui\Command\ImportUiBuilder::class,
            Ui\Command\ExportUiBuilder::class,
            Ui\Table\SelectUiBuilder::class,
            Ui\Table\TableUiBuilder::class,
            Ui\Table\ViewUiBuilder::class,
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
