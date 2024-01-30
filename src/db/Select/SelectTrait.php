<?php

namespace Lagdo\DbAdmin\Db\Select;

use Exception;
use Jaxon\Di\Container;

/**
 * Facade to table select functions
 */
trait SelectTrait
{
    /**
     * @return Container
     */
    abstract public function di(): Container;

    /**
     * Connect to a database server
     *
     * @return void
     */
    abstract public function connectToSchema();

    /**
     * Set the breadcrumbs items
     *
     * @param bool $showDatabase
     * @param array $breadcrumbs
     *
     * @return void
     */
    abstract protected function setBreadcrumbs(bool $showDatabase = false, array $breadcrumbs = []);

    /**
     * Get the proxy
     *
     * @return SelectFacade
     */
    protected function tableSelect(): SelectFacade
    {
        return $this->di()->g(SelectFacade::class);
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
    public function getSelectData(string $table, array $queryOptions = []): array
    {
        $this->connectToSchema();

        $this->setBreadcrumbs(true, [$this->trans->lang('Tables'), $table, $this->trans->lang('Select')]);

        $this->util->input()->table = $table;
        $this->util->input()->values = $queryOptions;
        return $this->tableSelect()->getSelectData($table, $queryOptions);
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

        $this->util->input()->table = $table;
        $this->util->input()->values = $queryOptions;
        return $this->tableSelect()->execSelect($table, $queryOptions);
    }
}
