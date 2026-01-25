<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql;

trait QueryTrait
{
    /**
     * @param int $page
     *
     * @return void
     */
    protected function savePageNumber(int $page): void
    {
        $queryOptions = $this->bag('dbadmin.select')->get($this->tabKey('options'), []);
        $queryOptions['page'] = $page;
        $this->bag('dbadmin.select')->set($this->tabKey('options'), $queryOptions);
    }

    /**
     * @param bool $withPage
     *
     * @return array
     */
    protected function getOptions(bool $withPage): array
    {
        // Default select options
        $options = $this->bag('dbadmin.select')->get('options');

        // Columns options
        $columns = $this->bag('dbadmin.select')->get($this->tabKey('columns'), []);
        $options['columns'] = $columns['column'] ?? [];

        // Filter options
        $filters = $this->bag('dbadmin.select')->get($this->tabKey('filters'), []);
        $options['where'] = $filters['where'] ?? [];

        // Sorting options
        $sorting = $this->bag('dbadmin.select')->get($this->tabKey('sorting'), []);
        $options['order'] = $sorting['order'] ?? [];
        $options['desc'] = $sorting['desc'] ?? [];

        // Pagination options
        if ($withPage) {
            $queryOptions = $this->bag('dbadmin.select')->get($this->tabKey('options'), []);
            $page = $queryOptions['page'] ?? -1;
            if ($page >= 0) {
                $options['page'] = $page;
            }
        }

        return $options;
    }

    /**
     * @return string
     */
    protected function getSelectQuery(): string
    {
        $table = $this->getTableName();
        $options = $this->getOptions(true);
        $select = $this->db()->getSelectData($table, $options);
        return $select->query;
    }
}
