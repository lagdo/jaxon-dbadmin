<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto\Dql;

class SelectDqDto
{
    /**
     * Select columns, empty for *.
     *
     * @var array<string>
     */
    public array $columns = []; // Columns in the SQL SELECT clause.

    /**
     * @var bool
     */
    public bool $grouped = false;

    /**
     * Expressions without aggregation.
     * Will be used for GROUP BY if an aggregation function is used.
     *
     * @var array
     */
    public array $groupBy = [];

    /**
     * @var array<string>
     */
    public array $selectableColumns = [];

    /**
     * @var array<string>
     */
    public array $filterableColumns = [];

    /**
     * @var array<string>
     */
    public array $sortableColumns = [];

    /**
     * @var array
     */
    public array $primaryColumns;
 
    /**
     * @var string
     */
    public string $query;

    /**
     * @var array
     */
    public array $filters;

    /**
     * @var array
     */
    public array $sorters;

    /**
     * @var array
     */
    public array $indexes;

    /**
     * @var array
     */
    public array $functions;

    /**
     * @var array
     */
    public array $grouping;

    /**
     * @var array
     */
    public array $operators;

    /**
     * @var SelectDqInputDto
     */
    public SelectDqInputDto $input;

    /**
     * @param SelectTableDto $table
     */
    public function __construct(public SelectTableDto $table)
    {
        $this->input = new SelectDqInputDto();
    }
}
