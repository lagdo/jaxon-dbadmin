<?php

use Lagdo\DbAdmin\Db\Driver\Exception\DbException;

use function Jaxon\jaxon;

return [
    'metadata' => [
        'format' => 'attributes',
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
    'exceptions' => [
        DbException::class => function(DbException $dbException) {
            $response = jaxon()->getResponse();
            $response->dialog()->warning($dbException->getMessage());
            return $response;
        },
    ],
];
