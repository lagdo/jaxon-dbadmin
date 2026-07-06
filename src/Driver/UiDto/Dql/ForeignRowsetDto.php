<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto\Dql;

use Lagdo\DbAdmin\Driver\Sql\Dto\ForeignKeyDto;

class ForeignRowsetDto
{
    /**
     * @var string
     */
    public string $labelColumn;

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
