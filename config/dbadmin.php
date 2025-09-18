<?php

use Lagdo\DbAdmin\Admin;
use Lagdo\DbAdmin\Command;
use Lagdo\DbAdmin\Config;
use Lagdo\DbAdmin\Db;
use Lagdo\DbAdmin\Driver;
use Lagdo\DbAdmin\Ui;

use function Jaxon\jaxon;

return [
    'metadata' => 'annotations',
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
                // The key below is defined by the corresponding plugin package.
                return $di->g('dbadmin_driver_' . $di->g('dbadmin_config_driver'));
            },
            // The database driver used in the application
            Db\CallbackDriver::class => function($di) {
                // This class will "clone" the selected driver, and define the callbacks.
                // By doing this, the driver classes will call the driver without the callbacks.
                $driver = new Db\CallbackDriver($di->g(Driver\DriverInterface::class));
                $timer = $di->g(Command\TimerService::class);
                $driver->addQueryCallback(fn() => $timer->stop());
                $logging = $di->g(Command\LoggingService::class);
                if ($logging !== null) {
                    $driver->addQueryCallback(fn(string $query) => $logging->saveCommand($query));
                }
                return $driver;
            },
            // Facades to the DB driver features
            Db\Facades\CommandFacade::class => function($di) {
                $dbFacade = $di->g(Db\DbFacade::class);
                $timer = $di->g(Command\TimerService::class);
                $logging = $di->g(Command\LoggingService::class);
                return new Db\Facades\CommandFacade($dbFacade, $timer, $logging);
            },
            Db\Facades\DatabaseFacade::class => function($di) {
                $dbFacade = $di->g(Db\DbFacade::class);
                return new Db\Facades\DatabaseFacade($dbFacade, $dbFacade->getServerOptions());
            },
            Db\Facades\ExportFacade::class => function($di) {
                $dbFacade = $di->g(Db\DbFacade::class);
                return new Db\Facades\ExportFacade($dbFacade);
            },
            Db\Facades\ImportFacade::class => function($di) {
                $dbFacade = $di->g(Db\DbFacade::class);
                $timer = $di->g(Command\TimerService::class);
                $logging = $di->g(Command\LoggingService::class);
                return new Db\Facades\ImportFacade($dbFacade, $timer, $logging);
            },
            Db\Facades\QueryFacade::class => function($di) {
                $dbFacade = $di->g(Db\DbFacade::class);
                return new Db\Facades\QueryFacade($dbFacade);
            },
            Db\Facades\SelectFacade::class => function($di) {
                $dbFacade = $di->g(Db\DbFacade::class);
                $timer = $di->g(Command\TimerService::class);
                return new Db\Facades\SelectFacade($dbFacade, $timer);
            },
            Db\Facades\ServerFacade::class => function($di) {
                $dbFacade = $di->g(Db\DbFacade::class);
                return new Db\Facades\ServerFacade($dbFacade, $dbFacade->getServerOptions());
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
            Config\AuthInterface::class => fn() =>
                new class implements Config\AuthInterface {
                    public function user(): string
                    {
                        return '';
                    }
                    public function role(): string
                    {
                        return '';
                    }
                },
            Config\UserFileReader::class => function($di) {
                $auth = $di->get(Config\AuthInterface::class);
                return new Config\UserFileReader($auth);
            },
            // Query logging
            Command\LoggingService::class => function($di) {
                $package = $di->g(Lagdo\DbAdmin\DbAdminPackage::class);
                $options = $package->getOption('logging.options');
                $database = $package->getOption('logging.database');
                $driverId = 'dbadmin_driver_' . ($database['driver'] ?? '');
                if (!$di->h($driverId) || !is_array($options) || !is_array($database)) {
                    return null;
                }

                $reader = $di->g(Config\UserFileReader::class);
                $auth = $di->g(Config\AuthInterface::class);
                $db = $di->g(Db\DbFacade::class);
                return new Command\LoggingService($auth, $db, $di->g($driverId),
                    $reader->getServerOptions($database), $options);
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
            Command\TimerService::class,
            // The db classes
            Admin\Admin::class,
            // The UI builders
            Ui\UiBuilder::class,
            Ui\InputBuilder::class,
            Ui\MenuBuilder::class,
            Ui\Database\ServerUiBuilder::class,
            Ui\Command\QueryUiBuilder::class,
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
