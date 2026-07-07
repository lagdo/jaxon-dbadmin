<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto\Dml;

class ForeignKeyDmDto
{
    /**
     * @param string $table
     * @param string $idColumn
     * @param string $labelColumn
     */
    public function __construct(public readonly string $table,
        public readonly string $idColumn, public readonly string $labelColumn)
    {}
}
