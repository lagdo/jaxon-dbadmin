<?php

use Lagdo\DbAdmin\Db\Exception\DbException;

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
            Lagdo\DbAdmin\Driver\DriverInterface::class => function($di) {
                // The key below is defined by the corresponding plugin package.
                return $di->g('dbadmin_driver_' . $di->g('dbadmin_config_driver'));
            },
            // Facades to the DB driver features
            Lagdo\DbAdmin\Db\Facades\CommandFacade::class => function($di) {
                $dbFacade = $di->g(Lagdo\DbAdmin\Db\DbFacade::class);
                return new Lagdo\DbAdmin\Db\Facades\CommandFacade($dbFacade);
            },
            Lagdo\DbAdmin\Db\Facades\DatabaseFacade::class => function($di) {
                $dbFacade = $di->g(Lagdo\DbAdmin\Db\DbFacade::class);
                return new Lagdo\DbAdmin\Db\Facades\DatabaseFacade($dbFacade, $dbFacade->getServerOptions());
            },
            Lagdo\DbAdmin\Db\Facades\ExportFacade::class => function($di) {
                $dbFacade = $di->g(Lagdo\DbAdmin\Db\DbFacade::class);
                return new Lagdo\DbAdmin\Db\Facades\ExportFacade($dbFacade);
            },
            Lagdo\DbAdmin\Db\Facades\ImportFacade::class => function($di) {
                $dbFacade = $di->g(Lagdo\DbAdmin\Db\DbFacade::class);
                return new Lagdo\DbAdmin\Db\Facades\ImportFacade($dbFacade);
            },
            Lagdo\DbAdmin\Db\Facades\QueryFacade::class => function($di) {
                $dbFacade = $di->g(Lagdo\DbAdmin\Db\DbFacade::class);
                return new Lagdo\DbAdmin\Db\Facades\QueryFacade($dbFacade);
            },
            Lagdo\DbAdmin\Db\Facades\SelectFacade::class => function($di) {
                $dbFacade = $di->g(Lagdo\DbAdmin\Db\DbFacade::class);
                return new Lagdo\DbAdmin\Db\Facades\SelectFacade($dbFacade);
            },
            Lagdo\DbAdmin\Db\Facades\ServerFacade::class => function($di) {
                $dbFacade = $di->g(Lagdo\DbAdmin\Db\DbFacade::class);
                return new Lagdo\DbAdmin\Db\Facades\ServerFacade($dbFacade, $dbFacade->getServerOptions());
            },
            Lagdo\DbAdmin\Db\Facades\TableFacade::class => function($di) {
                $dbFacade = $di->g(Lagdo\DbAdmin\Db\DbFacade::class);
                return new Lagdo\DbAdmin\Db\Facades\TableFacade($dbFacade);
            },
            Lagdo\DbAdmin\Db\Facades\UserFacade::class => function($di) {
                $dbFacade = $di->g(Lagdo\DbAdmin\Db\DbFacade::class);
                return new Lagdo\DbAdmin\Db\Facades\UserFacade($dbFacade);
            },
            Lagdo\DbAdmin\Db\Facades\ViewFacade::class => function($di) {
                $dbFacade = $di->g(Lagdo\DbAdmin\Db\DbFacade::class);
                return new Lagdo\DbAdmin\Db\Facades\ViewFacade($dbFacade);
            },
        ],
        'auto' => [
            // The translator
            Lagdo\DbAdmin\Translator::class,
            // The string manipulation class
            Lagdo\DbAdmin\Driver\Utils\Str::class,
            // The user input
            Lagdo\DbAdmin\Driver\Utils\Input::class,
            // The query history
            Lagdo\DbAdmin\Driver\Utils\History::class,
            // The utils class
            Lagdo\DbAdmin\Driver\Utils\Utils::class,
            // The facade to the database features
            Lagdo\DbAdmin\Db\DbFacade::class,
            // The db classes
            Lagdo\DbAdmin\Admin\Admin::class,
            // The UI builders
            Lagdo\DbAdmin\Ui\UiBuilder::class,
            Lagdo\DbAdmin\Ui\InputBuilder::class,
            Lagdo\DbAdmin\Ui\MenuBuilder::class,
            Lagdo\DbAdmin\Ui\Database\ServerUiBuilder::class,
            Lagdo\DbAdmin\Ui\Command\QueryUiBuilder::class,
            Lagdo\DbAdmin\Ui\Command\ImportUiBuilder::class,
            Lagdo\DbAdmin\Ui\Command\ExportUiBuilder::class,
            Lagdo\DbAdmin\Ui\Table\SelectUiBuilder::class,
            Lagdo\DbAdmin\Ui\Table\TableUiBuilder::class,
            Lagdo\DbAdmin\Ui\Table\ViewUiBuilder::class,
        ],
        'alias' => [
            // The translator
            Lagdo\DbAdmin\Driver\Utils\TranslatorInterface::class => Lagdo\DbAdmin\Translator::class,
        ],
    ],
    'exceptions' => [
        DbException::class => function(DbException $dbException) {
            $response = jaxon()->getResponse();
            $response->dialog->warning($dbException->getMessage());
            return $response;
        },
    ],
];
