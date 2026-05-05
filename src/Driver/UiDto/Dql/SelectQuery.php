<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto\Dql;

use Lagdo\DbAdmin\Support\Driver\AbstractDriverProxy;
use Lagdo\DbAdmin\Driver\Sql\Dto\ColumnDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\IndexDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\SelectInputDto;
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
     * @var SelectOptions|null
     */
    private SelectOptions|null $selectOptions = null;

    /**
     * @return SelectOptions
     */
    private function options(): SelectOptions
    {
        return $this->selectOptions ??= new SelectOptions($this);
    }

    /**
     * @param string $param
     *
     * @return array
     */
    private function inputArray(string $param): array
    {
        return $this->utils()->input->values[$param] ?? [];
    }

    /**
     * @param string $param
     * @param int $default
     *
     * @return int
     */
    private function inputInt(string $param, int $default): int
    {
        return (int)($this->utils()->input->values[$param] ?? $default);
    }

    /**
     * @param SelectDqDto $input
     *
     * @return void
     */
    private function setColumnsOptions(SelectDqDto $input): void
    {
        $input->rights = []; // privilege => 0
        $input->columnNames = []; // selectable columns
        $input->textLength = 0;

        foreach ($input->table->columns as $key => $column) {
            $name = $this->pageUi()->columnName($column);
            if (isset($column->privileges["select"]) && $name !== '') {
                $input->columnNames[$key] = html_entity_decode(strip_tags($name), ENT_QUOTES);
                if ($this->utils()->isShortable($column)) {
                    $input->textLength = $this->inputInt('length', 100);
                }
            }
            $input->rights[] = $column->privileges;
        }
    }

    /**
     * Find out foreign keys for each column
     *
     * @param SelectDqDto $input
     *
     * @return void
     */
    private function setForeignKeys(SelectDqDto $input): void
    {
        foreach ($input->table->foreignKeys as $foreignKey) {
            foreach ($foreignKey->source as $source) {
                $input->foreignKeys[$source] ??= [];
                $input->foreignKeys[$source][] = $foreignKey;
            }
        }
    }

    /**
     * @param array $value
     *
     * @return bool
     */
    private function colHasValidValue(array $value): bool
    {
        return $value['fun'] === 'count' ||
            ($value['col'] !== '' && (!$value['fun'] ||
                in_array($value['fun'], $this->engine()->functions()) ||
                in_array($value['fun'], $this->engine()->grouping())));
    }

    /**
     * @param array $where AND conditions
     * @param array $foreignKeys
     *
     * @return bool
     */
    // private function setSelectEmail(array $where, array $foreignKeys)
    // {
    //     return false;
    // }

    /**
     * @param SelectDqDto $input
     *
     * @return void
     */
    private function setSelectColumns(SelectDqDto $input): void
    {
        // Select expressions, empty for *.
        $input->columns = [];
        // Expressions without aggregation.
        // Will be used for GROUP BY if an aggregation function is used
        $input->groups = [];

        $inputColumns = array_filter($this->inputArray('columns'), $this->colHasValidValue(...));
        foreach ($inputColumns as $key => $value) {
            $columnName = '*';
            if ($value['col'] !== '') {
                $columnName = $this->statement()->escapeId($value['col']);
            }
            $input->columns[$key] = $this->pageUi()->applySqlFunction($value['fun'], $columnName);

            if (!in_array($value['fun'], $this->engine()->grouping())) {
                $input->groups[] = $input->columns[$key];
            }
        }
    }

    /**
     * @param array $value
     * @param array $columns
     *
     * @return string
     */
    private function getWhereCondition(array $value, array $columns): string
    {
        ['op' => $op, 'val' => $val, 'col' => $col] = $value;

        return match(true) {
            preg_match('~IN$~', $op) > 0 => " $op " .
                (($in = $this->statement()->processLength($val)) !== '' ? $in : '(NULL)'),
            $op === 'SQL' => " $val", // SQL injection
            $op === 'LIKE %%' => ' LIKE ' .
                $this->statement()->getUnconvertedFieldValue($columns[$col], "%$val%"),
            $op === 'ILIKE %%' => ' ILIKE ' .
                $this->statement()->getUnconvertedFieldValue($columns[$col], "%$val%"),
            $op === 'FIND_IN_SET' => ')',
            !preg_match('~NULL$~', $op) => " $op " .
                $this->statement()->getUnconvertedFieldValue($columns[$col], $val),
            default => " $op",
        };
    }

    /**
     * @param ColumnDto $column
     * @param array $value
     *
     * @return bool
     */
    private function selectFieldIsValid(ColumnDto $column, array $value): bool
    {
        ['op' => $op, 'val' => $val] = $value;
        $in = preg_match('~IN$~', $op) ? ',' : '';

        return (preg_match('~^[-\d.' . $in . ']+$~', $val) ||
                !preg_match('~' . $this->engine()->numberRegex() . '|bit~', $column->type)) &&
            (!preg_match("~[\x80-\xFF]~", $val) ||
                preg_match('~char|text|enum|set~', $column->type)) &&
            (!preg_match('~date|timestamp~', $column->type) ||
                preg_match('~^\d+-\d+-\d+~', $val));
    }

    /**
     * @param array $value
     * @param array $columns
     *
     * @return string
     */
    private function getSelectExpression(array $value, array $columns): string
    {
        ['op' => $op, 'col' => $col, 'val' => $val] = $value;
        $prefix = '';
        if ($op === 'FIND_IN_SET') {
            $quotedValue = $this->engine()->quote($val);
            $prefix = "{$op}({$quotedValue}, ";
        }
        $condition = $this->getWhereCondition($value, $columns);
        if ($col !== '') {
            return $prefix . $this->engine()->convertSearch($this->statement()->escapeId($col),
                $value, $columns[$col]) . $condition;
        }

        // find anywhere
        $columns = array_filter($columns, fn($column) => $this->selectFieldIsValid($column, $value));
        $clauses = array_map(function($column, $name) use($prefix, $value, $condition) {
            $name = $this->statement()->escapeId($name);
            $name = $this->engine()->convertSearch($name, $value, $column);
            return "$prefix$name$condition";
        }, $columns, array_keys($columns));

        return empty($clauses) ? '1 = 0' : ('(' . implode(' OR ', $clauses) . ')');
    }

    /**
     * @param IndexDto $index
     * @param int $position
     *
     * @return string
     */
    private function getMatchExpression(IndexDto $index, int $position): string
    {
        $inputFulltexts = $this->inputArray('fulltext');
        $inputBooleans = $this->inputArray('boolean');

        $fulltext = $inputFulltexts[$position] ?? '';
        $match = $this->engine()->quote($fulltext);
        if (isset($inputBooleans[$position])) {
            $match .= ' IN BOOLEAN MODE';
        }

        $columns = array_map($this->statement()->escapeId(...), $index->columns);
        $columns = implode(', ', $columns);
        return "MATCH ($columns) AGAINST ($match)";
    }

    /**
     * @param SelectDqDto $input
     *
     * @return void
     */
    private function setSelectWheres(SelectDqDto $input): void
    {
        $inputFulltexts = $this->inputArray('fulltext');
        $inputWheres = $this->inputArray('where');

        $input->wheres = [];
        foreach ($input->table->indexes as $i => $index) {
            $fulltext = $inputFulltexts[$i] ?? '';
            if ($index->type === 'FULLTEXT' && $fulltext !== '') {
                $input->wheres[] = $this->getMatchExpression($index, $i);
            }
        }
        foreach ($inputWheres as $value) {
            if (($value['col'] !== '' ||  $value['val'] !== '') &&
                in_array($value['op'], $this->engine()->operators())) {
                $input->wheres[] = $this->getSelectExpression($value, $input->table->columns);
            }
        }
    }

    /**
     * @param SelectDqDto $input
     *
     * @return void
     */
    private function setSelectOrders(SelectDqDto $input): void
    {
        $regexp = '~^((COUNT\(DISTINCT |[A-Z0-9_]+\()(`(?:[^`]|``)+`|"(?:[^"]|"")+")\)|COUNT\(\*\))$~';
        $orders = array_filter($this->inputArray('order'), fn(string $value) => $value !== '');
        $descs = $this->inputArray('desc');

        $input->orders = [];
        foreach ($orders as $key => $value) {
            if (preg_match($regexp, $value) !== false) {
                $value = $this->statement()->escapeId($value);
            }
            if ((int)($descs[$key] ?? 0) !== 0) {
                $value .= ' DESC';
            }
            $input->orders[] = $value;
        }
    }

    /**
     * @param SelectDqDto $input
     *
     * @return void
     */
    private function setPrimaryKey(SelectDqDto $input): void
    {
        $input->primaryColumns = [];
        // Take the first index only.
        $primaryIndexes = array_values(array_filter($input->table->indexes,
            fn(IndexDto $index) => $index->type === 'PRIMARY'));
        $primaryIndex = $primaryIndexes[0] ?? null;

        if ($primaryIndex !== null && count($input->columns) > 0) {
            $primaryColumns = array_filter($primaryIndex->columns, fn(string $columnName) =>
                in_array($this->statement()->escapeId($columnName), $input->columns));
            $input->primaryColumns = array_flip($primaryColumns);
        }

        $oid = $input->table->status->oid;
        if ($oid !== '' && count($input->primaryColumns) === 0) {
            $input->primaryColumns = [$oid => 0];
            // Make an index for the OID
            $index = new IndexDto();
            $index->type = "PRIMARY";
            $index->columns = [$oid];
            $input->table->indexes[] = $index;
        }
    }

    /**
     * @param SelectDqDto $input
     *
     * @return SelectInputDto
     */
    private function makeSelectDto(SelectDqDto $input): SelectInputDto
    {
        $clauses = $input->columns;
        $groups = $input->groups;
        if (empty($clauses)) {
            $clauses[] = "*";
            $names = array_keys($input->columnNames);
            $convert_columns = $this->statement()->convertColumns($names, $input->table->columns, $input->columns);
            if ($convert_columns) {
                $clauses[] = substr($convert_columns, 2);
            }
        }
        foreach ($input->columns as $key => $val) {
            $column = $columns[$this->statement()->unescapeId($val)] ?? null;
            if ($column && ($as = $this->statement()->convertColumn($column))) {
                $clauses[$key] = "$as AS $val";
            }
        }
        $isGroup = count($input->groups) < count($input->columns);
        if (!$isGroup && !empty($unselected)) {
            foreach ($unselected as $key => $val) {
                $clauses[] = $this->statement()->escapeId($key);
                if (!empty($groups)) {
                    $groups[] = $this->statement()->escapeId($key);
                }
            }
        }

        // From driver.inc.php
        return new SelectInputDto($input->table->name,
            $clauses, $input->wheres, $groups, $input->orders,
            $input->limit, $input->page);
    }

    /**
     * Get required data for select on tables
     *
     * @param SelectDqDto $input
     *
     * @return SelectDqDto
     * @throws Exception
     */
    public function prepareSelect(SelectDqDto $input): SelectDqDto
    {
        $this->options()->setDefaultOptions($input);

        // From select.inc.php
        $this->setColumnsOptions($input);
        if (!$input->columnNames && $this->engine()->support("table")) {
            throw new Exception($this->utils()->lang('Unable to select the table') .
                ($input->table->columns ? "." : ": " . $this->engine()->error()));
        }

        $this->setForeignKeys($input);
        $this->setSelectColumns($input);

        $this->setSelectWheres($input);
        $this->setSelectOrders($input);
        $input->limit = $this->inputInt('limit', 50);
        $this->setPrimaryKey($input);

        // $set = null;
        // if(isset($rights["insert"]) || !this->driver->support("table")) {
        //     $set = "";
        //     foreach((array) $selectOptions["where"] as $val) {
        //         if($foreignKeys[$val["col"]] && count($foreignKeys[$val["col"]]) == 1 && ($val["op"] == "="
        //             || (!$val["op"] && !preg_match('~[_%]~', $val["val"])) // LIKE in Editor
        //         )) {
        //             $set .= "&set" . urlencode("[" . $this->statement()->bracketEscape($val["col"]) . "]") . "=" . urlencode($val["val"]);
        //         }
        //     }
        // }
        // $this->pageUi()->selectLinks($tableStatus, $set);

        // if($page == "last")
        // {
        //     $isGroup = count($group) < count($select);
        //     $found_rows = $this->engine()->result($this->statement()->getRowCountQuery($table, $where, $isGroup, $group));
        //     $page = \floor(\max(0, $found_rows - 1) / $limit);
        // }

        $this->options()->setQueryOptions($input);
        $selectDto = $this->makeSelectDto($input);

        $query = $this->statement()->getTableSelectQuery($selectDto);
        // From adminer.inc.php
        $input->query = str_replace("\n", " ", $query);

        return $input;
    }
}
