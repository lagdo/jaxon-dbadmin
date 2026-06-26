<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto\Dql;

use Lagdo\DbAdmin\Driver\Sql\Dto\ColumnDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\ForeignKeyDto;

class QueryResultHeaderDto
{
    /**
     * @var string
     */
    public string $title;

    /**
     * @var string
     */
    public string $field; // The SQL clause in the select query.

    /**
     * @var ColumnDto
     */
    public ColumnDto $column;

    /**
     * @var ForeignKeyDto|null
     */
    public ForeignKeyDto|null $foreignKey = null;
}
