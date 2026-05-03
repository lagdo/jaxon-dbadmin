<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto\Dql;

class DqInputDto
{
    /**
     * @var array
     */
    public array $columns;

    /**
     * @var array
     */
    public array $columnNames; // Selectable columns.

    /**
     * @var array
     */
    public array $primaryColumns;

    /**
     * @var array
     */
    public array $foreignKeys;

    /**
     * @var array
     */
    public array $rights;

    /**
     * @var int
     */
    public int $textLength;

    /**
     * @var array
     */
    public array $groups;

    /**
     * @var array
     */
    public array $wheres;

    /**
     * @var array
     */
    public array $orders;

    /**
     * @var int
     */
    public int $limit;

    /**
     * @var int
     */
    public int $page;
 
    /**
     * @var string
     */
    public string $query;

    /**
     * @var array
     */
    public array $options;

    /**
     * @var array
     */
    public array $rows;

    /**
     * @var float
     */
    public float $duration;

    /**
     * @var array
     */
    public array $headers;

    /**
     * @var string|null
     */
    public string|null $error = null;

    /**
     * @param SelectTableDto $table
     * @param array $params
     */
    public function __construct(public SelectTableDto $table, public array $params)
    {}
}
