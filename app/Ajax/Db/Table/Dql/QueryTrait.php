<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Table\Dql;

trait QueryTrait
{
    /**
     * @return array
     */
    protected function getOptions(): array
    {
        // Default select options
        $options = $this->bag('dbadmin.select')->get('options');

        // Columns options
        $columns = $this->bag('dbadmin.select')->get('columns', []);
        $options['columns'] = $columns['column'] ?? [];

        // Filter options
        $filters = $this->bag('dbadmin.select')->get('filters', []);
        $options['where'] = $filters['where'] ?? [];

        // Sorting options
        $sorting = $this->bag('dbadmin.select')->get('sorting', []);
        $options['order'] = $sorting['order'] ?? [];
        $options['desc'] = $sorting['desc'] ?? [];

        return $options;
    }

    /**
     * @return string
     */
    protected function getSelectQuery(): string
    {
        return $this->db()
            ->getSelectData($this->getTableName(), $this->getOptions())['query'];
    }
}
