<?php

namespace Lagdo\DbAdmin\Support\Driver\Proxy;

use Lagdo\DbAdmin\Support\Driver\UiDto\Dql\DqInputDto;
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
     * @return DqInputDto
     * @throws Exception
     */
    public function getSelectParams(string $table, array $queryParams = []): DqInputDto
    {
        $this->connectToSchema();
        $this->utils()->input->table = $table;
        $this->utils()->input->values = $queryParams;
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
        $this->utils()->input->table = $table;
        $this->utils()->input->values = $queryParams;
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
        $this->utils()->input->table = $table;
        $this->utils()->input->values = $queryParams;
        return $this->selectProxy()->execSelect($table, $queryParams);
    }
}
