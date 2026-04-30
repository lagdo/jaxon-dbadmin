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
use function count;
use function implode;
use function intval;
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
     * @param DqInputDto $input
     *
     * @return void
     */
    private function setColumnsOptions(DqInputDto $input): void
    {
        $input->rights = []; // privilege => 0
        $input->selects = []; // selectable columns
        $input->textLength = 0;
        foreach ($input->columns as $key => $column) {
            $name = $this->pageUi()->columnName($column);
            if (isset($column->privileges["select"]) && $name !== '') {
                $input->selects[$key] = html_entity_decode(strip_tags($name), ENT_QUOTES);
                if ($this->utils()->isShortable($column)) {
                    $this->setSelectTextLength($input);
                }
            }
            $input->rights[] = $column->privileges;
        }
    }

    /**
     * Find out foreign keys for each column
     *
     * @param DqInputDto $input
     *
     * @return void
     */
    private function setForeignKeys(DqInputDto $input): void
    {
        $input->foreignKeys = [];
        foreach ($this->engine()->foreignKeys($input->table) as $foreignKey) {
            foreach ($foreignKey->source as $val) {
                $input->foreignKeys[$val][] = $foreignKey;
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
     * @param DqInputDto $input
     *
     * @return void
     */
    private function setSelectColumns(DqInputDto $input): void
    {
        $input->clauses = []; // select expressions, empty for *
        $input->groups = []; // expressions without aggregation - will be used for GROUP BY if an aggregation function is used
        $inputs = $this->utils()->input->values;
        foreach ($inputs['columns'] as $key => $value) {
            if ($this->colHasValidValue($value)) {
                $columnName = '*';
                if ($value['col'] !== '') {
                    $columnName = $this->statement()->escapeId($value['col']);
                }
                $input->clauses[$key] = $this->pageUi()->applySqlFunction($value['fun'], $columnName);
                if (!in_array($value['fun'], $this->engine()->grouping())) {
                    $input->groups[] = $input->clauses[$key];
                }
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
        $op = $value['op'];
        $val = $value['val'];
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
        $fulltext = $this->utils()->input->values['fulltext'][$position] ?? '';
        $match = $this->engine()->quote($fulltext);
        if (isset($this->utils()->input->values['boolean'][$position])) {
            $match .= ' IN BOOLEAN MODE';
        }

        $selects = array_map($this->statement()->escapeId(...), $index->selects);
        $selects = implode(', ', $selects);

        return "MATCH ($selects) AGAINST ($match)";
    }

    /**
     * @param DqInputDto $input
     *
     * @return void
     */
    private function setSelectWheres(DqInputDto $input): void
    {
        $inputs = $this->utils()->input->values;
        $input->wheres = [];
        foreach ($input->indexes as $i => $index) {
            $fulltext = $inputs['fulltext'][$i] ?? '';
            if ($index->type === 'FULLTEXT' && $fulltext !== '') {
                $input->wheres[] = $this->getMatchExpression($index, $i);
            }
        }
        foreach ((array)$inputs['where'] as $value) {
            if (($value['col'] !== '' ||  $value['val'] !== '') &&
                in_array($value['op'], $this->engine()->operators())) {
                $input->wheres[] = $this->getSelectExpression($value, $input->columns);
            }
        }
    }

    /**
     * @param DqInputDto $input
     *
     * @return void
     */
    private function setSelectOrders(DqInputDto $input): void
    {
        $inputs = $this->utils()->input->values;
        $input->orders = [];
        foreach ($inputs['order'] as $key => $value) {
            if ($value !== '') {
                $regexp = '~^((COUNT\(DISTINCT |[A-Z0-9_]+\()(`(?:[^`]|``)+`|"(?:[^"]|"")+")\)|COUNT\(\*\))$~';
                if (preg_match($regexp, $value) !== false) {
                    $value = $this->statement()->escapeId($value);
                }
                if (isset($inputs['desc'][$key]) && intval($inputs['desc'][$key]) !== 0) {
                    $value .= ' DESC';
                }
                $input->orders[] = $value;
            }
        }
    }

    /**
     * @param DqInputDto $input
     *
     * @return void
     */
    private function setSelectLimit(DqInputDto $input): void
    {
        $input->limit = intval($this->utils()->input->values['limit'] ?? 50);
    }

    /**
     * @param DqInputDto $input
     *
     * @return void
     */
    private function setSelectTextLength(DqInputDto $input): void
    {
        $input->textLength = intval($this->utils()->input->values['length'] ?? 100);
    }

    /**
     * @param DqInputDto $input
     *
     * @return void
     */
    private function setPrimaryKey(DqInputDto $input): void
    {
        $primary = null;
        $input->unselected = [];
        foreach ($input->indexes as $index) {
            if ($index->type === "PRIMARY") {
                $primary = array_flip($index->columns);
                $input->unselected = ($input->clauses ? $primary : []);
                foreach ($input->unselected as $key => $val) {
                    if (in_array($this->statement()->escapeId($key), $input->clauses)) {
                        unset($input->unselected[$key]);
                    }
                }
                break;
            }
        }

        $oid = $input->tableStatus->oid;
        if ($oid && !$primary) {
            /*$primary = */$input->unselected = [$oid => 0];
            // Make an index for the OID
            $index = new IndexDto();
            $index->type = "PRIMARY";
            $index->columns = [$oid];
            $input->indexes[] = $index;
        }
    }

    /**
     * @param DqInputDto $input
     *
     * @return void
     */
    private function setDqInputDto(DqInputDto $input): void
    {
        $clauses = $input->clauses;
        $groups = $input->groups;
        if (empty($clauses)) {
            $clauses[] = "*";
            $names = array_keys($input->selects);
            $convert_columns = $this->statement()->convertValues($names, $input->columns, $input->clauses);
            if ($convert_columns) {
                $clauses[] = substr($convert_columns, 2);
            }
        }
        foreach ($input->clauses as $key => $val) {
            $column = $columns[$this->statement()->unescapeId($val)] ?? null;
            if ($column && ($as = $this->statement()->convertValue($column))) {
                $clauses[$key] = "$as AS $val";
            }
        }
        $isGroup = count($input->groups) < count($input->clauses);
        if (!$isGroup && !empty($unselected)) {
            foreach ($unselected as $key => $val) {
                $clauses[] = $this->statement()->escapeId($key);
                if (!empty($groups)) {
                    $groups[] = $this->statement()->escapeId($key);
                }
            }
        }

        // From driver.inc.php
        $input->tableSelect = new SelectInputDto($input->table,
            $clauses, $input->wheres, $groups, $input->orders,
            $input->limit, $input->page);
    }

    /**
     * Get required data for select on tables
     *
     * @param DqInputDto $input
     *
     * @return DqInputDto
     * @throws Exception
     */
    public function prepareSelect(DqInputDto $input): DqInputDto
    {
        $this->options()->setDefaultOptions($input);

        // From select.inc.php
        $input->columns = $this->engine()->columns($input->table);
        $this->setColumnsOptions($input);
        if (!$input->selects && $this->engine()->support("table")) {
            throw new Exception($this->utils()->lang('Unable to select the table') .
                ($input->columns ? "." : ": " . $this->engine()->error()));
        }

        $input->indexes = $this->engine()->indexes($input->table);
        $this->setForeignKeys($input);
        $this->setSelectColumns($input);

        $this->setSelectWheres($input);
        $this->setSelectOrders($input);
        $this->setSelectLimit($input);
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

        $this->options()->setSelectOptions($input);
        $this->setDqInputDto($input);

        $query = $this->statement()->getTableSelectQuery($input->tableSelect);
        // From adminer.inc.php
        $input->query = str_replace("\n", " ", $query);

        return $input;
    }
}
