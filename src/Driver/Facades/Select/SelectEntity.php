<?php

namespace Lagdo\DbAdmin\Db\Driver\Facades\Select;

use Lagdo\DbAdmin\Driver\Entity\TableEntity;
use Lagdo\DbAdmin\Driver\Entity\TableSelectEntity;

class SelectEntity
{
    /**
     * @var array
     */
    public array $fields;

    /**
     * @var array
     */
    public array $rights;

    /**
     * @var array
     */
    public array $columns;

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
    public array $select;

    /**
     * @var array
     */
    public array $group;

    /**
     * @var array
     */
    public array $where;

    /**
     * @var array
     */
    public array $order;

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
     * @var TableSelectEntity
     */
    public TableSelectEntity $tableSelect;

    /**
     * @param string $table
     * @param string $tableName
     * @param TableEntity $tableStatus
     * @param array $queryOptions
     */
    public function __construct(public string $table, public string $tableName,
        public TableEntity $tableStatus, public array $queryOptions)
    {}
}
