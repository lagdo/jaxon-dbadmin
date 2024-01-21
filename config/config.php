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
        ],
        'auto' => [
            // The user input
            Lagdo\DbAdmin\Driver\Input::class,
            // The facade to the database features
            Lagdo\DbAdmin\DbAdmin::class,
            // The translator
            Lagdo\DbAdmin\Db\Translator::class,
            // The db classes
            Lagdo\DbAdmin\Db\Util::class,
            Lagdo\DbAdmin\Db\Admin::class,
            // The template builder
            Lagdo\DbAdmin\Ui\Builder::class,
        ],
        'alias' => [
            // The translator
            Lagdo\DbAdmin\Driver\TranslatorInterface::class => Lagdo\DbAdmin\Db\Translator::class,
            // The db util
            Lagdo\DbAdmin\Driver\UtilInterface::class => Lagdo\DbAdmin\Db\Util::class,
        ],
    ],
];
