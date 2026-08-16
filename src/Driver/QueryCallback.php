<?php

namespace Lagdo\DbAdmin\Support\Driver;

use Lagdo\DbAdmin\Driver\Sql\Connection\QueryResultInterface;

interface QueryCallback
{
    /**
     * @param string $query
     *
     * @return void
     */
    public function beforeQueryExec(string $query): void;

    /**
     * @param string $query
     * @param QueryResultInterface|bool $result
     *
     * @return void
     */
    public function afterQueryExec(string $query, QueryResultInterface|bool $result): void;
}
