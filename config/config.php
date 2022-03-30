<?php

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
    'container' => require( __DIR__ . '/container.php'),
];
