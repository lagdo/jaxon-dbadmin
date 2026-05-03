<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto\Dql;

use Lagdo\DbAdmin\Driver\Sql\Dto\ColumnDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\ForeignKeyDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\IndexDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\TableDto;

class SelectTableDto
{
    /**
     * @var TableDto
     */
    public TableDto $status;

    /**
     * @var array<ColumnDto>
     */
    public array $columns;

    /**
     * @var array<IndexDto>
     */
    public array $indexes;

    /**
     * @var array<ForeignKeyDto>
     */
    public array $foreignKeys;

    /**
     * @param string $name
     */
    public function __construct(public string $name)
    {}
}
