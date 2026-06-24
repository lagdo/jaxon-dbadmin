<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto\Dql;

use Lagdo\DbAdmin\Driver\Sql\Dto\SelectColumnDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\SelectFilterDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\SelectSorterDto;

class SelectDqInputDto
{
    /**
     * @var array<SelectColumnDto>
     */
    public array $columns;

    /**
     * @var array<SelectFilterDto>
     */
    public array $filters;

    /**
     * @var array<SelectSorterDto>
     */
    public array $sorters;

    /**
     * @var array<string>
     */
    public array $fullTexts;

    /**
     * @var array<string>
     */
    public array $booleans;

    /**
     * @var int
     */
    public int $limit;

    /**
     * @var int
     */
    public int $textLength;

    /**
     * @var bool
     */
    public bool $total;

    /**
     * @var int
     */
    public int $page;
 }
