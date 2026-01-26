<?php

namespace Lagdo\DbAdmin\Db\UiData\Dql;

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
     * @param SelectDto $selectDto
     *
     * @return void
     */
    public function setDefaultOptions(SelectDto $selectDto): void
    {
        $defaultOptions = [
            'columns' => [],
            'where' => [],
            'order' => [],
            'desc' => [],
            'fulltext' => [],
            'limit' => '50',
            'length' => '100',
            'page' => '1',
        ];
        foreach ($defaultOptions as $name => $value) {
            $this->utils->input->values[$name] ??= $value;
            $selectDto->queryOptions[$name] ??= $value;
        }
        $page = intval($selectDto->queryOptions['page']);
        if ($page > 0) {
            $page -= 1; // Page numbers start at 0 here, instead of 1.
        }
        $selectDto->queryOptions['page'] = $page;
        $selectDto->page = $page;
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
     * @param SelectDto $selectDto
     *
     * @return void
     */
    public function setSelectOptions(SelectDto $selectDto): void
    {
        $selectDto->options = [
            'columns' => $this->getColumnsOptions($selectDto->select,
                $selectDto->columns, $selectDto->queryOptions),
            'filters' => $this->getFiltersOptions($selectDto->columns,
                $selectDto->indexes, $selectDto->queryOptions),
            'sorting' => $this->getSortingOptions($selectDto->columns,
                $selectDto->queryOptions),
            'limit' => $this->getLimitOptions($selectDto->limit),
            'length' => $this->getLengthOptions($selectDto->textLength),
            // 'action' => $this->getActionOptions($selectDto->indexes),
        ];
    }
}
