<?php

namespace Lagdo\DbAdmin\Db\Page\Dql;

use Lagdo\DbAdmin\Driver\Utils\Utils;
use Lagdo\DbAdmin\Driver\DriverInterface;

use function intval;

class SelectOptions
{
    /**
     * The constructor
     *
     * @param DriverInterface $driver
     * @param Utils $utils
     */
    public function __construct(private DriverInterface $driver, private Utils $utils)
    {}

    /**
     * @param SelectEntity $selectEntity
     *
     * @return void
     */
    public function setDefaultOptions(SelectEntity $selectEntity): void
    {
        $defaultOptions = [
            'columns' => [],
            'where' => [],
            'order' => [],
            'desc' => [],
            'fulltext' => [],
            'limit' => '50',
            'text_length' => '100',
            'page' => '1',
        ];
        foreach ($defaultOptions as $name => $value) {
            if (!isset($this->utils->input->values[$name])) {
                $this->utils->input->values[$name] = $value;
            }
            if (!isset($selectEntity->queryOptions[$name])) {
                $selectEntity->queryOptions[$name] = $value;
            }
        }
        $page = intval($selectEntity->queryOptions['page']);
        if ($page > 0) {
            $page -= 1; // Page numbers start at 0 here, instead of 1.
        }
        $selectEntity->queryOptions['page'] = $page;
        $selectEntity->page = $page;
    }

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

    /**
     * Print action box in select
     *
     * @param array $indexes
     *
     * @return array
     */
    // private function getActionOptions(array $indexes)
    // {
    //     $columns = [];
    //     foreach ($indexes as $index) {
    //         $current_key = \reset($index->columns);
    //         if ($index->type != "FULLTEXT" && $current_key) {
    //             $columns[$current_key] = 1;
    //         }
    //     }
    //     $columns[""] = 1;
    //     return ['columns' => $columns];
    // }

    /**
     * Print command box in select
     *
     * @return bool whether to print default commands
     */
    // private function getCommandOptions()
    // {
    //     return !$this->driver->isInformationSchema($this->driver->database());
    // }

    /**
     * Print import box in select
     *
     * @return bool whether to print default import
     */
    // private function getImportOptions()
    // {
    //     return !$this->driver->isInformationSchema($this->driver->database());
    // }

    /**
     * Print extra text in the end of a select form
     *
     * @param array $emailFields Fields holding e-mails
     * @param array $columns Selectable columns
     *
     * @return array
     */
    // private function getEmailOptions(array $emailFields, array $columns)
    // {
    // }

    /**
     * @param SelectEntity $selectEntity
     *
     * @return void
     */
    public function setSelectOptions(SelectEntity $selectEntity): void
    {
        $selectEntity->options = [
            'columns' => $this->getColumnsOptions($selectEntity->select,
                $selectEntity->columns, $selectEntity->queryOptions),
            'filters' => $this->getFiltersOptions($selectEntity->columns,
                $selectEntity->indexes, $selectEntity->queryOptions),
            'sorting' => $this->getSortingOptions($selectEntity->columns,
                $selectEntity->queryOptions),
            'limit' => $this->getLimitOptions($selectEntity->limit),
            'length' => $this->getLengthOptions($selectEntity->textLength),
            // 'action' => $this->getActionOptions($selectEntity->indexes),
        ];
    }
}
