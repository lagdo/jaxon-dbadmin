<?php

use Jaxon\Di\Container;
use Lagdo\DbAdmin\Db;
use Lagdo\DbAdmin\Db\Config;
use Lagdo\DbAdmin\Db\Driver\Facades;
use Lagdo\DbAdmin\Db\Service;
use Lagdo\DbAdmin\Driver;
use Lagdo\DbAdmin\Ui;

jaxon()->callback()->boot(function() {
    $di = jaxon()->di();
    // Register a driver for each database server.
    $serverConfig = $di->g(Config\ServerConfig::class);
    foreach($serverConfig->getServerIds() as $server) {
        // The driver options
        $di->set("dbadmin_driver_options_$server", fn() =>
            $serverConfig->getServerConfig($server));
        // The driver itself
        $di->set("dbadmin_driver_$server", function() use($di, $server) {
            $options = $di->g("dbadmin_driver_options_$server");
            return Db\Driver\AppDriver::createDriver($options);
        });
    }
});

return [
    'set' => [
        // Selected database driver options
        'dbadmin_driver_options' => function(Container $di) {
            $server = $di->g('dbadmin_config_server');
            return $di->g("dbadmin_driver_options_$server");
        },
        // Selected database driver
        Driver\DriverInterface::class => function(Container $di) {
            $server = $di->g('dbadmin_config_server');
            return $di->g("dbadmin_driver_$server");
        },
        // Facades to the DB driver features
        Facades\CommandFacade::class => function(Container $di) {
            $dbFacade = $di->g(Db\Driver\DbFacade::class);
            $timer = $di->g(Service\TimerService::class);
            $logger = $di->g(Service\Admin\QueryLogger::class);
            return new Facades\CommandFacade($dbFacade, $timer, $logger);
        },
        Facades\DatabaseFacade::class => function(Container $di) {
            $dbFacade = $di->g(Db\Driver\DbFacade::class);
            $options = $di->g('dbadmin_driver_options');
            return new Facades\DatabaseFacade($dbFacade, $options);
        },
        Facades\ExportFacade::class => function(Container $di) {
            $dbFacade = $di->g(Db\Driver\DbFacade::class);
            return new Facades\ExportFacade($dbFacade);
        },
        Facades\ImportFacade::class => function(Container $di) {
            $dbFacade = $di->g(Db\Driver\DbFacade::class);
            $timer = $di->g(Service\TimerService::class);
            $logger = $di->g(Service\Admin\QueryLogger::class);
            return new Facades\ImportFacade($dbFacade, $timer, $logger);
        },
        Facades\QueryFacade::class => function(Container $di) {
            $dbFacade = $di->g(Db\Driver\DbFacade::class);
            return new Facades\QueryFacade($dbFacade);
        },
        Facades\SelectFacade::class => function(Container $di) {
            $dbFacade = $di->g(Db\Driver\DbFacade::class);
            $timer = $di->g(Service\TimerService::class);
            return new Facades\SelectFacade($dbFacade, $timer);
        },
        Facades\ServerFacade::class => function(Container $di) {
            $dbFacade = $di->g(Db\Driver\DbFacade::class);
            $options = $di->g('dbadmin_driver_options');
            return new Facades\ServerFacade($dbFacade, $options);
        },
        Facades\TableFacade::class => function(Container $di) {
            $dbFacade = $di->g(Db\Driver\DbFacade::class);
            return new Facades\TableFacade($dbFacade);
        },
        Facades\UserFacade::class => function(Container $di) {
            $dbFacade = $di->g(Db\Driver\DbFacade::class);
            return new Facades\UserFacade($dbFacade);
        },
        Facades\ViewFacade::class => function(Container $di) {
            $dbFacade = $di->g(Db\Driver\DbFacade::class);
            return new Facades\ViewFacade($dbFacade);
        },
        'dbadmin_auth_service' => fn(Container $di) =>
            $di->h(Config\AuthInterface::class) ?
                // Custom auth service defined.
                $di->g(Config\AuthInterface::class) :
                // Default auth service when none is defined.
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
        Config\ConfigReader::class => fn() => new Config\ConfigReader(),
        Config\UserFileReader::class => fn(Container $di) =>
            new Config\UserFileReader($di->g('dbadmin_auth_service')),
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
        Db\UiData\AppPage::class,
        // The facade to the database features
        Db\Driver\DbFacade::class,
        // The Timer service
        Service\TimerService::class,
        // The UI builders
        Ui\AuditUiBuilder::class,
        Ui\UiBuilder::class,
        Ui\InputBuilder::class,
        Ui\MenuBuilder::class,
        Ui\Data\EditUiBuilder::class,
        Ui\Select\OptionsUiBuilder::class,
        Ui\Select\ResultUiBuilder::class,
        Ui\Select\SelectUiBuilder::class,
        Ui\Database\ServerUiBuilder::class,
        Ui\Command\QueryUiBuilder::class,
        Ui\Command\AuditUiBuilder::class,
        Ui\Command\ImportUiBuilder::class,
        Ui\Command\ExportUiBuilder::class,
        Ui\Table\TableUiBuilder::class,
        Ui\Table\ViewUiBuilder::class,
        Ui\Table\ColumnUiBuilder::class,
    ],
    'alias' => [
        // The translator
        Driver\Utils\TranslatorInterface::class => Db\Translator::class,
    ],
];
