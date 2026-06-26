<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto;

/**
 * @template RowsetDto
 */
class QueryResultDto
{
    /**
     * @var int
     */
    public int $queries = 0;

    /**
     * @var int
     */
    public int $errors = 0;

    /**
     * @var float
     */
    public float $duration = 0;

    /**
     * @var array<RowsetDto>
     */
    public array $rowsets = [];

    /**
     * @var string|null
     */
    public string|null $error = null;

    /**
     * @var string|null
     */
    public string|null $message = null;

    /**
     * @var array<string>
     */
    public array $batchBuffer = [];
}
