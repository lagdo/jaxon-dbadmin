<?php

namespace Lagdo\DbAdmin\Db\Driver\Proxy;

use Lagdo\DbAdmin\Db\UiData\Dql\SelectDto;
use Exception;

/**
 * Proxy to table select functions
 */
trait SelectTrait
{
    use AbstractTrait;

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
     * @param array $queryOptions The query options
     *
     * @return SelectDto
     * @throws Exception
     */
    public function getSelectData(string $table, array $queryOptions = []): SelectDto
    {
        $this->connectToSchema();
        $this->utils()->input->table = $table;
        $this->utils()->input->values = $queryOptions;
        return $this->selectProxy()->getSelectData($table, $queryOptions);
    }

    /**
     * Get required data for create/update on tables
     *
     * @param string $table The table name
     * @param array $queryOptions The query options
     *
     * @return int
     * @throws Exception
     */
    public function countSelect(string $table, array $queryOptions = []): int
    {
        $this->connectToSchema();
        $this->utils()->input->table = $table;
        $this->utils()->input->values = $queryOptions;
        return $this->selectProxy()->countSelect($table, $queryOptions);
    }

    /**
     * Get required data for create/update on tables
     *
     * @param string $table The table name
     * @param array $queryOptions The query options
     *
     * @return array
     * @throws Exception
     */
    public function execSelect(string $table, array $queryOptions = []): array
    {
        $this->connectToSchema();
        $this->utils()->input->table = $table;
        $this->utils()->input->values = $queryOptions;
        return $this->selectProxy()->execSelect($table, $queryOptions);
    }
}
