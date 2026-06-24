<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto;

class RowsetDto
{
    /**
     * @var array
     */
    public array $tables = []; // This data is not yet used.

    /**
     * @var array<string>
     */
    public array $headers = [];

    /**
     * @var array<array<string>>
     */
    public array $rows = [];

    /**
     * @var int
     */
    public int $rowCount = 0;

    /**
     * @var int
     */
    public int $affectedRows = 0;

    /**
     * @var string
     */
    public string $query;

    /**
     * @param string|null $error
     * @param string|null $message
     */
    public function __construct(public string|null $error = null,
        public string|null $message = null)
    {}
}
