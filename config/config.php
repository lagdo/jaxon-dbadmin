<?php

use Lagdo\DbAdmin\Ui\Builder;
use Lagdo\DbAdmin\Ui\Builder\Bootstrap3Builder;

return [
    'directories' => [
        __DIR__ . '/../app/Ajax' => [
            'namespace' => 'Lagdo\\DbAdmin\\App\\Ajax',
            'autoload' => false,
            'classes' => require( __DIR__ . '/classes.php'),
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
            'template' => [
                'option' => 'template',
                'default' => 'bootstrap3',
            ],
        ],
    ],
    'container' => [
        Lagdo\DbAdmin\DbAdmin::class => function($di) {
            $package = $di->get(Lagdo\DbAdmin\Package::class);
            return new Lagdo\DbAdmin\DbAdmin($package);
        },
        Builder::class => function() {
            $bootstrap3Builder = new Bootstrap3Builder();
            return new Builder($bootstrap3Builder);
        },
    ],
];
