<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto\Dql;

use Lagdo\DbAdmin\Driver\Sql\Dto\ColumnDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\SelectColumnDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\SelectFilterDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\SelectSorterDto;
use Lagdo\DbAdmin\Support\Driver\AbstractDriverProxy;
use Exception;

use function array_filter;
use function array_map;
use function html_entity_decode;
use function preg_match;
use function strip_tags;

class SelectOptions extends AbstractDriverProxy
{
    /**
     * @param array $inputs
     * @param string $param
     *
     * @return array
     */
    private function inputArray(array $inputs, string $param): array
    {
        return $inputs[$param] ?? [];
    }

    /**
     * @param array $inputs
     * @param string $param
     * @param int $default
     *
     * @return int
     */
    private function inputInt(array $inputs, string $param, int $default): int
    {
        return (int)($inputs[$param] ?? $default);
    }

    /**
     * @param array $inputs
     * @param string $param
     * @param bool $default
     *
     * @return bool
     */
    private function inputBool(array $inputs, string $param, bool $default): bool
    {
        return (bool)($inputs[$param] ?? $default);
    }

    /**
     * @param array $column
     *
     * @return SelectColumnDto
     */
    private function newColumnDto(array $column): SelectColumnDto
    {
        return new SelectColumnDto($column['column'], $column['func']);
    }

    /**
     * @param array $filter
     *
     * @return SelectFilterDto
     */
    private function newFilterDto(array $filter): SelectFilterDto
    {
        return new SelectFilterDto($filter['column'],
            $filter['operator'], $filter['operand']);
    }

    /**
     * @param array $sorter
     *
     * @return SelectSorterDto
     */
    private function newSorterDto(array $sorter): SelectSorterDto
    {
        return new SelectSorterDto($sorter['column'], $sorter['desc'] ?? false);
    }

    /**
     * @param SelectDqDto $select
     * @param array $inputs
     *
     * @return void
     */
    private function setSelectColumns(SelectDqDto $select, array $inputs): void
    {
        $isValid = fn(SelectColumnDto $column) =>
            $column->isValid($select->functions, $select->grouping);
        $columns = array_map($this->newColumnDto(...), $this->inputArray($inputs, 'columns'));
        $select->input->columns = array_filter($columns, $isValid);
    }

    /**
     * @param SelectDqDto $select
     * @param array $inputs
     *
     * @return void
     */
    private function setSelectFilters(SelectDqDto $select, array $inputs): void
    {
        // Todo: Not yet implemented.
        $select->input->fullTexts = []; // $this->inputArray($inputs, 'fullTexts');
        $select->input->booleans = []; // $this->inputArray($inputs, 'booleans');

        $isValid = fn(SelectFilterDto $filter) => $filter->isValid($select->operators);
        $filters = array_map($this->newFilterDto(...), $this->inputArray($inputs, 'filters'));
        $select->input->filters = array_filter($filters, $isValid);
    }

    /**
     * @param SelectDqDto $select
     * @param array $inputs
     *
     * @return void
     */
    private function setSelectSorters(SelectDqDto $select, array $inputs): void
    {
        $isValid = fn(SelectSorterDto $sorter) => $sorter->isValid();
        $sorters = array_map($this->newSorterDto(...), $this->inputArray($inputs, 'sorters'));
        $select->input->sorters = array_filter($sorters, $isValid);
    }

    /**
     * @param SelectDqDto $select
     *
     * @return void
     */
    private function setFieldsOptions(SelectDqDto $select): void
    {
        $select->functions = $this->engine()->functions();
        $select->grouping = $this->engine()->grouping();
        $select->operators = $this->engine()->operators();
        $select->indexes = $select->table->indexes;
        // $fulltexts = [];
        // foreach ($select->table->indexes as $i => $index) {
        //     $fulltexts[$i] = $index->type === 'FULLTEXT' ?
        //         $this->utils()->html($select->params['fulltext'][$i] ?? '') : '';
        // }
        // $select->fullTexts = $fulltexts;

        foreach ($select->table->columns as $key => $column) {
            $name = $this->pageUi()->columnName($column);
            if ($name !== '') {
                $name = html_entity_decode(strip_tags($name), ENT_QUOTES);
                if (isset($column->privileges["select"])) {
                    $select->selectableColumns[$key] = $name;
                }
                if (isset($column->privileges["where"])) {
                    $select->filterableColumns[$key] = $name;
                }
                if (isset($column->privileges["order"])) {
                    $select->sortableColumns[$key] = $name;
                }
            }
        }
    }

    /**
     * @param SelectTableDto $table
     * @param array $inputs
     *
     * @return SelectDqDto
     */
    public function createSelectDto(SelectTableDto $table, array $inputs): SelectDqDto
    {
        $select = new SelectDqDto($table);

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
           $inputs[$name] ??= $value;
        }

        $page = (int)$inputs['page'];
        // Page numbers start at 0 here, instead of 1.
        $select->input->page = $page > 0 ? $page - 1 : 0;
        $select->input->limit = $this->inputInt($inputs, 'limit', 50);
        $select->input->total = $this->inputBool($inputs, 'total', true);
        $select->input->textLength = $this->inputInt($inputs, 'length', 100);
        $select->input->loadForeigns = $this->inputBool($inputs, 'foreigns', false);

        $this->setFieldsOptions($select);
        $this->setSelectColumns($select, $inputs);
        $this->setSelectFilters($select, $inputs);
        $this->setSelectSorters($select, $inputs);

        $this->checkColumnDto($select);

        return $select;
    }

    /**
     * @param array<ColumnDto> $columns
     * @param string $columnName
     *
     * @return ColumnDto
     * @throws Exception
     */
    private function findColumnDto(array $columns, string $columnName): ColumnDto
    {
        if (!isset($columns[$columnName])) {
            throw new Exception("No column with name $columnName found.");
        }

        return $columns[$columnName];
    }

    /**
     * @param ColumnDto $column
     * @param SelectFilterDto $filter
     *
     * @return bool
     */
    private function columnIsSearchable(ColumnDto $column, SelectFilterDto $filter): bool
    {
        $in = preg_match('~IN$~', $filter->operator) ? ',' : '';
        $number = $this->engine()->numberRegex();

        return isset($column->privileges["where"]) &&
            (preg_match("~^[-\d.$in]+$~", $filter->operand) ||
                !preg_match("~$number|bit~", $column->type)) &&
            (!preg_match("~[\x80-\xFF]~", $filter->operand) ||
                preg_match('~char|text|enum|set~', $column->type)) &&
            (!preg_match('~date|timestamp~', $column->type) ||
                preg_match('~^\d+-\d+-\d+~', $filter->operand));
    }

    /**
     * @param SelectDqDto $select
     *
     * @return void
     * @throws Exception
     */
    private function checkColumnDto(SelectDqDto $select): void
    {
        $columns = $select->table->columns;

        foreach ($select->input->columns as $column) {
            if ($column->func === 'count' && $column->columnName === '') {
                $column->column = null;
                continue;
            }

            if (!isset($select->selectableColumns[$column->columnName])) {
                throw new Exception("Invalid column name {$column->columnName}.");
            }

            $column->column = $this->findColumnDto($columns, $column->columnName);
        }

        foreach ($select->input->filters as $filter) {
            if ($filter->columnName === '') {
                // Find anywhere.
                $filter->columns = array_filter($select->table->columns,
                    fn(ColumnDto $column) => $this->columnIsSearchable($column, $filter));
                continue;
            }

            if (!isset($select->filterableColumns[$filter->columnName])) {
                throw new Exception("Cannot filter on column {$filter->columnName}.");
            }

            $filter->columns = [$this->findColumnDto($columns, $filter->columnName)];
        }

        foreach ($select->input->sorters as $sorter) {
            if (!isset($select->sortableColumns[$sorter->columnName])) {
                throw new Exception("Cannot sort on column {$sorter->columnName}.");
            }

            $sorter->column = $this->findColumnDto($columns, $sorter->columnName);
        }
    }

    /**
     * Print action box in select
     *
     * @param SelectDqDto $select
     *
     * @return array
     */
    // private function getActionOptions(SelectDqDto $select)
    // {
    //     $columns = [];
    //     foreach ($select->table->indexes as $index) {
    //         $current_key = \reset($index->columns);
    //         if ($index->type != 'FULLTEXT' && $current_key) {
    //             $columns[$current_key] = 1;
    //         }
    //     }
    //     $columns[''] = 1;
    //     return ['columns' => $columns];
    // }

    /**
     * Print command box in select
     *
     * @return bool whether to print default commands
     */
    // private function getCommandOptions()
    // {
    //     return !$this->engine()->isInformationSchema($this->engine()->database());
    // }

    /**
     * Print import box in select
     *
     * @return bool whether to print default import
     */
    // private function getImportOptions()
    // {
    //     return !$this->engine()->isInformationSchema($this->engine()->database());
    // }

    /**
     * Print extra text in the end of a select form
     *
     * @param array $emailColumns Columns holding e-mails
     * @param array $columns Selectable columns
     *
     * @return array
     */
    // private function getEmailOptions(array $emailColumns, array $columns)
    // {}
}
