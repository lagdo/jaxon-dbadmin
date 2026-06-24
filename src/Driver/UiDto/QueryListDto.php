<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto;

class QueryListDto
{
    /**
     * @param string|null $error
     * @param array|null $queries
     */
    public function __construct(public string|null $error = null,
        public array|null $queries = null)
    {}
}
