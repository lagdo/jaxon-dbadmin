<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto\Dql;

use Lagdo\DbAdmin\Support\Driver\AbstractDriverProxy;
use Lagdo\DbAdmin\Driver\Sql\Dto\ColumnDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\IndexDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\QueryClauseDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\SelectDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\SelectFilterDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\SelectSorterDto;
use Exception;

use function array_filter;
use function array_keys;
use function array_map;
use function array_values;
use function count;
use function implode;
use function in_array;
use function preg_match;
use function str_replace;
use function substr;

/**
 * Prepare a select query using the user form values.
 */
class SelectQuery extends AbstractDriverProxy
{
    /**
     * @var string
     */
    private string $sorterRegexp = '~^((COUNT\(DISTINCT |[A-Z0-9_]+\()(`(?:[^`]|``)+`|"(?:[^"]|"")+")\)|COUNT\(\*\))$~';

    /**
     * @param SelectFilterDto $filter
     *
     * @return string
     */
    private function getWhereInClauseValue(SelectFilterDto $filter): string
    {
        $in = $this->statement()->processLength($filter->operand);
        return "{$filter->operator} " . ($in !== '' ? $in : '(NULL)');
    }

    /**
     * @param string $keyword
     * @param ColumnDto $column
     * @param SelectFilterDto $filter
     *
     * @return string
     */
    private function getWhereLikeClauseValue(string $keyword,
        ColumnDto $column, SelectFilterDto $filter): string
    {
        return "$keyword " .
            $this->statement()->getUnconvertedFieldValue($column, "%{$filter->operand}%");
    }

    /**
     * @param ColumnDto $column
     * @param SelectFilterDto $filter
     *
     * @return string
     */
    private function getWhereDefaultClauseValue(ColumnDto $column, SelectFilterDto $filter): string
    {
        return "{$filter->operator} " .
            $this->statement()->getUnconvertedFieldValue($column, $filter->operand);
    }

    /**
     * @param ColumnDto $column
     * @param SelectFilterDto $filter
     *
     * @return string
     */
    private function getWhereClause(ColumnDto $column, SelectFilterDto $filter): string
    {
        $quotedValue = $filter->operator === 'FIND_IN_SET' ?
            $this->engine()->quote($filter->operand) : '';
        $search = $this->engine()->convertSearch($filter, $column);

        return match(true) {
            preg_match('~IN$~', $filter->operator) > 0 => "{$search} " .
                $this->getWhereInClauseValue($filter),
            $filter->operator === 'SQL' => "{$search} {$filter->operand}", // SQL injection
            $filter->operator === 'LIKE %%' => "{$search} " .
                $this->getWhereLikeClauseValue('LIKE', $column, $filter),
            $filter->operator === 'ILIKE %%' => "{$search} " .
                $this->getWhereLikeClauseValue('ILIKE', $column, $filter),
            $filter->operator === 'FIND_IN_SET' => "FIND_IN_SET({$quotedValue}, {$search})",
            !preg_match('~NULL$~', $filter->operator) => "{$search} " .
                $this->getWhereDefaultClauseValue($column, $filter),
            default => "{$search} {$filter->operand}",
        };
    }

    /**
     * @param SelectFilterDto $filter
     *
     * @return string
     */
    private function getSelectFilter(SelectFilterDto $filter): string
    {
        $count = count($filter->columns);
        if ($count === 0) {
            // No valid column for find anywhere.
            return '1 = 0';
        }

        $callback = fn(ColumnDto $column) => $this->getWhereClause($column, $filter);
        $clauses = array_values(array_map($callback, $filter->columns));
        return $count === 1 ? $clauses[0] : ('(' . implode(' OR ', $clauses) . ')');
    }

    /**
     * @param IndexDto $index
     * @param string|null $fullText
     * @param bool|null $boolean
     *
     * @return string|null
     */
    private function getMatchFilter(IndexDto $index,
        string|null $fullText, bool|null $boolean): string|null
    {
        if ($index->type !== 'FULLTEXT' || !$fullText) {
            return null;
        }

        $match = $this->engine()->quote($fullText ?? '');
        if ($boolean) {
            $match .= ' IN BOOLEAN MODE';
        }

        $columns = implode(', ', array_map($this->statement()->escapeId(...), $index->columns));
        return "MATCH ($columns) AGAINST ($match)";
    }

    /**
     * @param SelectDqDto $select
     *
     * @return void
     */
    private function setSelectFilters(SelectDqDto $select): void
    {
        $indexFilters = array_map($this->getMatchFilter(...), $select->table->indexes,
            $select->input->fullTexts, $select->input->booleans);
        $indexFilters = array_filter($indexFilters, fn($filter) => $filter !== null);

        $selectFilters = array_map($this->getSelectFilter(...), $select->input->filters);

        $select->filters = [...$indexFilters, ...$selectFilters];
    }

    /**
     * @param SelectSorterDto $sorter
     *
     * @return string
     */
    private function getSelectSorterClause(SelectSorterDto $sorter): string
    {
        $columnName = preg_match($this->sorterRegexp, $sorter->columnName) !== false ?
            $this->statement()->escapeId($sorter->columnName) : $sorter->columnName;

        return $sorter->desc ? "$columnName DESC" : $columnName;
    }

    /**
     * @param SelectDqDto $select
     *
     * @return void
     */
    private function setSelectSorters(SelectDqDto $select): void
    {
        $select->sorters = count($select->input->sorters) === 0 ? [] :
            array_map($this->getSelectSorterClause(...), $select->input->sorters);
    }

    /**
     * @param SelectDqDto $select
     *
     * @return void
     */
    private function setPrimaryKey(SelectDqDto $select): void
    {
        $select->primaryColumns = [];
        // Take the first index only.
        $primaryIndexes = array_values(array_filter($select->table->indexes,
            fn(IndexDto $index) => $index->type === 'PRIMARY'));
        $primaryIndex = $primaryIndexes[0] ?? null;

        if ($primaryIndex !== null && count($select->columns) > 0) {
            $primaryColumns = array_filter($primaryIndex->columns, fn(string $columnName) =>
                in_array($this->statement()->escapeId($columnName), $select->columns));
            $select->primaryColumns = array_flip($primaryColumns);
        }

        $oid = $select->table->status->oid;
        if ($oid !== '' && count($select->primaryColumns) === 0) {
            $select->primaryColumns = [$oid => 0];
            // Make an index for the OID
            $index = new IndexDto();
            $index->type = "PRIMARY";
            $index->columns = [$oid];
            $select->table->indexes[] = $index;
        }
    }

    /**
     * @param SelectDqDto $select
     *
     * @return SelectDto
     */
    private function makeSelectDto(SelectDqDto $select): SelectDto
    {
        $columns = $select->columns;
        $groupBy = $select->groupBy;

        if (count($columns) === 0) {
            $columns[] = "*";
            $names = array_keys($select->selectableColumns);
            $convert_columns = $this->statement()
                ->convertColumns($names, $select->table->columns, $select->columns);
            if ($convert_columns) {
                $columns[] = substr($convert_columns, 2);
            }
        }

        foreach ($select->columns as $key => $val) {
            $columnName = $this->statement()->unescapeId($val);
            $column = $select->table->columns[$columnName] ?? null;
            if ($column && ($as = $this->statement()->convertColumn($column))) {
                $columns[$key] = "$as AS $val";
            }
        }

        if (!$select->grouped && !empty($unselected)) {
            foreach ($unselected as $key => $val) {
                $columns[] = $this->statement()->escapeId($key);
                if (!empty($select->groupBy)) {
                    $groupBy[] = $this->statement()->escapeId($key);
                }
            }
        }

        $tableName = [$this->statement()->escapeTableName($select->table->name)];
        $clauses = [
            new QueryClauseDto('SELECT', ', ', $columns),
            new QueryClauseDto('FROM', ', ', $tableName),
            new QueryClauseDto('WHERE', ' AND ', $select->filters),
            !$select->grouped || count($groupBy) === 0 ? null :
                new QueryClauseDto('GROUP BY', ', ', $groupBy),
            new QueryClauseDto('ORDER BY', ', ', $select->sorters),
        ];
        $limit = $select->input->limit;
        $offset = $select->input->page > 0 ? $limit * $select->input->page : 0;

        return new SelectDto($clauses, $limit, $offset, $select->grouped, $groupBy);
    }

    /**
     * @param SelectDqDto $select
     *
     * @return void
     */
    private function setSelectColumns(SelectDqDto $select): void
    {
        foreach ($select->input->columns as $key => $value) {
            $clause = $value->column === null ? '*' :
                $this->statement()->escapeId($value->columnName);
            $clause = $this->pageUi()->applySqlFunction($value->func, $clause);

            $select->columns[$key] = $clause;
            $grouped = in_array($value->func, $this->engine()->grouping());
            $select->grouped = $select->grouped || $grouped;
            if (!$grouped) {
                $select->groupBy[] = $clause;
            }
        }
    }

    /**
     * @param SelectDqDto $select
     *
     * @return SelectDqDto
     * @throws Exception
     */
    public function prepareSelect(SelectDqDto $select): SelectDqDto
    {
        if ($this->engine()->support("table") && count($select->selectableColumns) === 0) {
            throw new Exception($this->utils()->lang('Unable to select the table') .
                ($select->table->columns ? "." : ": " . $this->engine()->error()));
        }

        $this->setSelectColumns($select);
        $this->setSelectFilters($select);
        $this->setSelectSorters($select);
        $this->setPrimaryKey($select);

        // $set = null;
        // if(isset($rights["insert"]) || !this->driver->support("table")) {
        //     $set = "";
        //     foreach((array) $selectOptions["where"] as $val) {
        //         if($foreignKeys[$val["col"]] && count($foreignKeys[$val["col"]]) == 1 && ($val["op"] == "="
        //             || (!$val["op"] && !preg_match('~[_%]~', $val["val"])) // LIKE in Editor
        //         )) {
        //             $col = $this->statement()->bracketEscape($val["col"]);
        //             $set .= "&set" . urlencode("[$col]") . "=" . urlencode($val["val"]);
        //         }
        //     }
        // }
        // $this->pageUi()->selectLinks($tableStatus, $set);

        return $select;
    }

    /**
     * @param SelectDqDto $select
     *
     * @return SelectDqDto
     * @throws Exception
     */
    public function makeSelect(SelectDqDto $select): SelectDqDto
    {
        $selectDto = $this->makeSelectDto($select);
        $query = $this->statement()->getTableSelectQuery($selectDto);
        $select->query = str_replace("\n", " ", $query);

        return $select;
    }
}
