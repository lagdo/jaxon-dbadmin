<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto;

class ExecResultDto
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
     * @var array<ResultsetDto>
     */
    public array $resultsets = [];

    /**
     * @var string|null
     */
    public string|null $error = null;

    /**
     * @var string|null
     */
    public string|null $message = null;
}
