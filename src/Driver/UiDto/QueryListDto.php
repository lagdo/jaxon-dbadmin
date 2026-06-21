<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto;

class QueryListDto
{
    /**
     * @var bool
     */
    public bool $withTimer = false;

    /**
     * @var bool
     */
    public bool $withLogger = false;

    /**
     * @param string|null $error
     * @param array|null $queries
     */
    public function __construct(public string|null $error = null,
        public array|null $queries = null)
    {}
}
