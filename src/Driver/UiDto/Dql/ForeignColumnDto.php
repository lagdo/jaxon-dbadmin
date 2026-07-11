<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto\Dql;

use Lagdo\DbAdmin\Driver\Sql\Dto\ForeignKeyDto;
use Closure;

class ForeignColumnDto
{
    /**
     * @param ForeignKeyDto $fkey
     * @param string $idColumn
     * @param Closure $select
     * @param Closure|null $search
     * @param array<string> $joins
     */
    public function __construct(public ForeignKeyDto $fkey, public string $idColumn,
        public Closure $select, public Closure|null $search, public array $joins = [])
    {}
}
