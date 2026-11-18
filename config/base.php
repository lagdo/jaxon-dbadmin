<?php

return [
    'metadata' => [
        'format' => 'attributes',
    ],
    'views' => [
        'dbadmin::editor' => [
            'directory' => dirname(__DIR__) . '/templates/editor',
            'extension' => '.html',
            'renderer' => 'jaxon',
        ],
        'dbadmin::page' => [
            'directory' => dirname(__DIR__) . '/templates/page',
            'extension' => '.php',
            'renderer' => 'jaxon',
        ],
    ],
];
