<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto\Dql;

use Lagdo\DbAdmin\Driver\Sql\Dto\ForeignKeyDto;
use Closure;

class ForeignRowsetDto
{
    /**
     * @var string
     */
    public string $idColumn;

    /**
     * @var Closure
     */
    public Closure $select;

    /**
     * @var Closure|null
     */
    public Closure|null $filter;

    /**
     * @var array
     */
    public array $values = [];

    /**
     * @param ForeignKeyDto $fkey
     */
    public function __construct(public ForeignKeyDto $fkey)
    {}
}
