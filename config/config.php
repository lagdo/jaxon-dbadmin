<?php

use Lagdo\DbAdmin\Db\Exception\DbException;

use function Jaxon\jaxon;

return [
    'metadata' => 'annotations',
    'directories' => [
        __DIR__ . '/../app/Ajax' => [
            'namespace' => 'Lagdo\\DbAdmin\\App\\Ajax',
            'autoload' => false,
        ],
    ],
    'views' => [
        'adminer::codes' => [
            'directory' => __DIR__ . '/../templates/codes',
            'extension' => '.php',
            'renderer' => 'jaxon',
        ],
        'adminer::views' => [
            'directory' => __DIR__ . '/../templates/views',
            'extension' => '.php',
            'renderer' => 'jaxon',
        ],
        'adminer::templates' => [
            'directory' => __DIR__ . '/../templates/views',
            'extension' => '.php',
            'renderer' => 'jaxon',
            'template' => [
                'option' => 'template',
                'default' => 'bootstrap3',
            ],
        ],
    ],
    'container' => [
        'set' => [
            // The package config builder
            'dbadmin_config_builder' => function($di) {
                $config = $di->getPackageConfig(Lagdo\DbAdmin\Package::class);
                return $config->getOption('template', 'bootstrap3');
            },
            // Selected database driver
            Lagdo\DbAdmin\Driver\DriverInterface::class => function($di) {
                // The key below is defined by the corresponding plugin package.
                return $di->g('dbadmin_driver_' . $di->g('dbadmin_config_driver'));
            },
            // Selected HTML template builder
            Lagdo\UiBuilder\BuilderInterface::class => function($di) {
                // The key below is defined by the corresponding plugin package.
                return $di->g('dbadmin_builder_' . $di->g('dbadmin_config_builder'));
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
            // The user input
            Lagdo\DbAdmin\Driver\Input::class,
            // The facade to the database features
            Lagdo\DbAdmin\Db\DbFacade::class,
            // The translator
            Lagdo\DbAdmin\Translator::class,
            // The db classes
            Lagdo\DbAdmin\Util::class,
            // The template builders
            Lagdo\DbAdmin\Ui\UiBuilder::class,
            Lagdo\DbAdmin\Ui\MenuBuilder::class,
            Lagdo\DbAdmin\Ui\PageBuilder::class,
        ],
        'alias' => [
            // The translator
            Lagdo\DbAdmin\Driver\TranslatorInterface::class => Lagdo\DbAdmin\Translator::class,
            // The db util
            Lagdo\DbAdmin\Driver\UtilInterface::class => Lagdo\DbAdmin\Util::class,
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
