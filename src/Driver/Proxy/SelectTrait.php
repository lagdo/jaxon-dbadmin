<?php

namespace Lagdo\DbAdmin\Support\Driver\Proxy;

use Lagdo\DbAdmin\Support\Driver\UiDto\Dql\SelectDqDto;
use Exception;

/**
 * Proxy to table select functions
 */
trait SelectTrait
{
    use AbstractProxyTrait;

    /**
     * Get the proxy
     *
     * @return SelectProxy
     */
    protected function selectProxy(): SelectProxy
    {
        return $this->di()->g(SelectProxy::class);
    }

    /**
     * Get required data for create/update on tables
     *
     * @param string $table The table name
     * @param array $queryParams The user params
     *
     * @return SelectDqDto
     * @throws Exception
     */
    public function getSelectParams(string $table, array $queryParams = []): SelectDqDto
    {
        $this->connectToSchema();
        $this->utils()->setInputTable($table);
        $this->utils()->setInputValues($queryParams);
        return $this->selectProxy()->getSelectParams($table, $queryParams);
    }

    /**
     * Get required data for create/update on tables
     *
     * @param string $table The table name
     * @param array $queryParams The user params
     *
     * @return int
     * @throws Exception
     */
    public function countSelect(string $table, array $queryParams = []): int
    {
        $this->connectToSchema();
        $this->utils()->setInputTable($table);
        $this->utils()->setInputValues($queryParams);
        return $this->selectProxy()->countSelect($table, $queryParams);
    }

    /**
     * Get required data for create/update on tables
     *
     * @param string $table The table name
     * @param array $queryParams The user params
     *
     * @return array
     * @throws Exception
     */
    public function execSelect(string $table, array $queryParams = []): array
    {
        $this->connectToSchema();
        $this->utils()->setInputTable($table);
        $this->utils()->setInputValues($queryParams);
        return $this->selectProxy()->execSelect($table, $queryParams);
    }
}
