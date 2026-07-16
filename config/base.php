<?php

use Lagdo\DbAdmin\Support\Exception\DbException;

use function Jaxon\jaxon;

return [
    'metadata' => [
        'format' => 'attributes',
    ],
    'views' => [
        'dbadmin::editor' => [
            'directory' => __DIR__ . '/../templates/editor',
            'extension' => '.html',
            'renderer' => 'jaxon',
        ],
        'dbadmin::sql' => [
            'directory' => __DIR__ . '/../templates/sql',
            'extension' => '.php',
            'renderer' => 'jaxon',
        ],
        'pagination' => [
            'directory' => __DIR__ . '/../templates/pagination',
            'extension' => '.php',
            'renderer' => 'jaxon',
        ],
    ],
    'exceptions' => [
        DbException::class => function(DbException $dbException) {
            $response = jaxon()->getResponse();
            $response->dialog()->warning($dbException->getMessage());
            return $response;
        },
    ],
];
