<?php

return [
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
                $config = $di->getPackageConfig(Lagdo\DbAdmin\App\Package::class);
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
            Lagdo\DbAdmin\Db\Command\CommandFacade::class => function($di) {
                $dbFacade = $di->g(Lagdo\DbAdmin\Db\DbFacade::class);
                $facade = new Lagdo\DbAdmin\Db\Command\CommandFacade();
                $facade->init($dbFacade);
                return $facade;
            },
            Lagdo\DbAdmin\Db\Database\DatabaseFacade::class => function($di) {
                $dbFacade = $di->g(Lagdo\DbAdmin\Db\DbFacade::class);
                $facade = new Lagdo\DbAdmin\Db\Database\DatabaseFacade($dbFacade->getServerOptions());
                $facade->init($dbFacade);
                return $facade;
            },
            Lagdo\DbAdmin\Db\Export\ExportFacade::class => function($di) {
                $dbFacade = $di->g(Lagdo\DbAdmin\Db\DbFacade::class);
                $facade = new Lagdo\DbAdmin\Db\Export\ExportFacade();
                $facade->init($dbFacade);
                return $facade;
            },
            Lagdo\DbAdmin\Db\Import\ImportFacade::class => function($di) {
                $dbFacade = $di->g(Lagdo\DbAdmin\Db\DbFacade::class);
                $facade = new Lagdo\DbAdmin\Db\Import\ImportFacade();
                $facade->init($dbFacade);
                return $facade;
            },
            Lagdo\DbAdmin\Db\Query\QueryFacade::class => function($di) {
                $dbFacade = $di->g(Lagdo\DbAdmin\Db\DbFacade::class);
                $facade = new Lagdo\DbAdmin\Db\Query\QueryFacade();
                $facade->init($dbFacade);
                return $facade;
            },
            Lagdo\DbAdmin\Db\Select\SelectFacade::class => function($di) {
                $dbFacade = $di->g(Lagdo\DbAdmin\Db\DbFacade::class);
                $facade = new Lagdo\DbAdmin\Db\Select\SelectFacade();
                $facade->init($dbFacade);
                return $facade;
            },
            Lagdo\DbAdmin\Db\Server\ServerFacade::class => function($di) {
                $dbFacade = $di->g(Lagdo\DbAdmin\Db\DbFacade::class);
                $facade = new Lagdo\DbAdmin\Db\Server\ServerFacade($dbFacade->getServerOptions());
                $facade->init($dbFacade);
                return $facade;
            },
            Lagdo\DbAdmin\Db\Table\TableFacade::class => function($di) {
                $dbFacade = $di->g(Lagdo\DbAdmin\Db\DbFacade::class);
                $facade = new Lagdo\DbAdmin\Db\Table\TableFacade();
                $facade->init($dbFacade);
                return $facade;
            },
            Lagdo\DbAdmin\Db\User\UserFacade::class => function($di) {
                $dbFacade = $di->g(Lagdo\DbAdmin\Db\DbFacade::class);
                $facade = new Lagdo\DbAdmin\Db\User\UserFacade();
                $facade->init($dbFacade);
                return $facade;
            },
            Lagdo\DbAdmin\Db\View\ViewFacade::class => function($di) {
                $dbFacade = $di->g(Lagdo\DbAdmin\Db\DbFacade::class);
                $facade = new Lagdo\DbAdmin\Db\View\ViewFacade();
                $facade->init($dbFacade);
                return $facade;
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
            // The template builder
            Lagdo\DbAdmin\Ui\UiBuilder::class,
        ],
        'alias' => [
            // The translator
            Lagdo\DbAdmin\Driver\TranslatorInterface::class => Lagdo\DbAdmin\Translator::class,
            // The db util
            Lagdo\DbAdmin\Driver\UtilInterface::class => Lagdo\DbAdmin\Util::class,
        ],
    ],
];
