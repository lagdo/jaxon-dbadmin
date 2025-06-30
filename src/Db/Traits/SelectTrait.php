<?php

namespace Lagdo\DbAdmin\Db\Traits;

use Exception;
use Lagdo\DbAdmin\Db\Facades\SelectFacade;

/**
 * Facade to table select functions
 */
trait SelectTrait
{
    use AbstractTrait;

    /**
     * Get the facade
     *
     * @return SelectFacade
     */
    protected function selectFacade(): SelectFacade
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
        $this->bcdb()->breadcrumb($this->utils->trans->lang('Tables'))
            ->breadcrumb($table)->breadcrumb($this->utils->trans->lang('Select'));
        $this->utils->input->table = $table;
        $this->utils->input->values = $queryOptions;
        return $this->selectFacade()->getSelectData($table, $queryOptions);
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
        $this->utils->input->table = $table;
        $this->utils->input->values = $queryOptions;
        return $this->selectFacade()->countSelect($table, $queryOptions);
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
        $this->utils->input->table = $table;
        $this->utils->input->values = $queryOptions;
        return $this->selectFacade()->execSelect($table, $queryOptions);
    }
}
