<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto\Dql;

use Lagdo\DbAdmin\Driver\Sql\Dto\SelectInputDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\TableDto;

class DqInputDto
{
    /**
     * @var array
     */
    public array $selects; // selectable columns

    /**
     * @var array
     */
    public array $columns;

    /**
     * @var array
     */
    public array $clauses;

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
    public array $indexes;

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
     * @var array
     */
    public array $unselected;

    /**
     * @var int
     */
    public int $limit;

    /**
     * @var int
     */
    public int $page;

    /**
     * @var array
     */
    public array $foreignKeys;
 
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
     * @var array
     */
    public array $names;

    /**
     * @var string|null
     */
    public string|null $error = null;

    /**
     * @var SelectInputDto
     */
    public SelectInputDto $tableSelect;

    /**
     * @param string $table
     * @param string $tableName
     * @param TableDto $tableStatus
     * @param array $queryOptions
     */
    public function __construct(public string $table, public string $tableName,
        public TableDto $tableStatus, public array $queryOptions)
    {}
}
