<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto\Ddl;

use Lagdo\DbAdmin\Driver\Sql\Dto\ColumnDto;

/**
 * A referencable column for a foreign key.
 */
class ReferencableDto
{
    /**
     * @param string $table
     * @param ColumnDto $column The primary key column
     */
    public function __construct(public string $table, public ColumnDto $column)
    {}
}
