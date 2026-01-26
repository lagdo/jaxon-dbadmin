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
        $queryOptions = $this->getSelectBag('options', []);
        $queryOptions['page'] = $page;
        $this->setSelectBag('options', $queryOptions);
    }

    /**
     * @return array
     */
    protected function getOptions(): array
    {
        // Default select options
        $options = $this->getSelectBag('options');

        // Columns options
        $columns = $this->getSelectBag('columns', []);
        $options['columns'] = $columns['column'] ?? [];

        // Filter options
        $filters = $this->getSelectBag('filters', []);
        $options['where'] = $filters['where'] ?? [];

        // Sorting options
        $sorting = $this->getSelectBag('sorting', []);
        $options['order'] = $sorting['order'] ?? [];
        $options['desc'] = $sorting['desc'] ?? [];

        // Pagination options
        if (($options['page'] ?? 0) < 0) {
            $options['page'] = 0;
        }

        return $options;
    }

    /**
     * @return string
     */
    protected function getSelectQuery(): string
    {
        $table = $this->getCurrentTable();
        $options = $this->getOptions();
        $select = $this->db()->getSelectData($table, $options);
        return $select->query;
    }
}
