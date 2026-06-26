<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto\Dql;

class QueryResultRowDto
{
    /**
     * @var array<string, mixed>|null
     */
    public array|null $editValues;

    /**
     * @var array<string>|null
     */
    public array|null $nullValues;

    /**
     * @var array
     */
    public array $columns;

    /**
     * @var string
     */
    public string $bagId;

    /**
     * @var string
     */
    public string $rowMenu;
}
