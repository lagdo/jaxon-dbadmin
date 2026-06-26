<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto\Dql;

class SelectRowsetDto
{
    /**
     * @var array
     */
    public array $tables = []; // This data is not yet used.

    /**
     * @var array<QueryResultHeaderDto>
     */
    public array $headers = [];

    /**
     * @var array<QueryResultRowDto>
     */
    public array $rows = [];

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
