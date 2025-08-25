<?php

namespace Lagdo\DbAdmin\Db\Facades\Select;

trait SelectTrait
{
    /**
     * Print columns box in select
     *
     * @param array $select Result of processSelectColumns()[0]
     * @param array $columns Selectable columns
     * @param array $options
     * @return array
     */
    private function getColumnsOptions(array $select, array $columns, array $options): array
    {
        return [
            'select' => $select,
            'values' => (array)$options["columns"],
            'columns' => $columns,
            'functions' => $this->driver->functions(),
            'grouping' => $this->driver->grouping(),
        ];
    }

    /**
     * Print search box in select
     *
     * @param array $columns Selectable columns
     * @param array $indexes
     * @param array $options
     *
     * @return array
     */
    private function getFiltersOptions(array $columns, array $indexes, array $options): array
    {
        $fulltexts = [];
        foreach ($indexes as $i => $index) {
            $fulltexts[$i] = $index->type == "FULLTEXT" ?
                $this->utils->str->html($options["fulltext"][$i] ?? '') : '';
        }
        return [
            // 'where' => $where,
            'values' => (array)$options["where"],
            'columns' => $columns,
            'indexes' => $indexes,
            'operators' => $this->driver->operators(),
            'fulltexts' => $fulltexts,
        ];
    }

    /**
     * Print order box in select
     *
     * @param array $columns Selectable columns
     * @param array $options
     *
     * @return array
     */
    private function getSortingOptions(array $columns, array $options): array
    {
        $values = [];
        $descs = (array)$options["desc"];
        foreach ((array)$options["order"] as $key => $value) {
            $values[] = [
                'col' => $value,
                'desc' => $descs[$key] ?? 0,
            ];
        }
        return [
            // 'order' => $order,
            'values' => $values,
            'columns' => $columns,
        ];
    }

    /**
     * Print limit box in select
     *
     * @param string $limit Result of processSelectLimit()
     *
     * @return array
     */
    private function getLimitOptions(string $limit): array
    {
        return ['value' => $this->utils->str->html($limit)];
    }

    /**
     * Print text length box in select
     *
     * @param int $textLength Result of processSelectLength()
     *
     * @return array
     */
    private function getLengthOptions(int $textLength): array
    {
        return ['value' => $textLength === 0 ? 0 : $this->utils->str->html($textLength)];
    }
}
