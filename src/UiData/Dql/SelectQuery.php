<?php

namespace Lagdo\DbAdmin\Db\UiData\Dql;

use Lagdo\DbAdmin\Db\UiData\AppPage;
use Lagdo\DbAdmin\Driver\DriverInterface;
use Lagdo\DbAdmin\Driver\Dto\IndexDto;
use Lagdo\DbAdmin\Driver\Dto\TableFieldDto;
use Lagdo\DbAdmin\Driver\Dto\TableSelectDto;
use Lagdo\DbAdmin\Driver\Utils\Utils;
use Exception;

use function count;
use function implode;
use function intval;
use function in_array;
use function preg_match;
use function str_replace;

/**
 * Prepare a select query using the user provided options.
 */
class SelectQuery
{
    /**
     * @var SelectOptions
     */
    private $selectOptions;

    /**
     * The constructor
     *
     * @param AppPage $page
     * @param DriverInterface $driver
     * @param Utils $utils
     */
    public function __construct(private AppPage $page,
        private DriverInterface $driver, private Utils $utils)
    {
        $this->selectOptions = new SelectOptions($this->driver, $this->utils);
    }

    /**
     * @param SelectDto $selectDto
     *
     * @return void
     */
    private function setFieldsOptions(SelectDto $selectDto): void
    {
        $selectDto->rights = []; // privilege => 0
        $selectDto->columns = []; // selectable columns
        $selectDto->textLength = 0;
        foreach ($selectDto->fields as $key => $field) {
            $name = $this->page->fieldName($field);
            if (isset($field->privileges["select"]) && $name != "") {
                $selectDto->columns[$key] = html_entity_decode(strip_tags($name), ENT_QUOTES);
                if ($this->page->isShortable($field)) {
                    $this->setSelectTextLength($selectDto);
                }
            }
            $selectDto->rights[] = $field->privileges;
        }
    }

    /**
     * Find out foreign keys for each column
     *
     * @param SelectDto $selectDto
     *
     * @return void
     */
    private function setForeignKeys(SelectDto $selectDto): void
    {
        $selectDto->foreignKeys = [];
        foreach ($this->driver->foreignKeys($selectDto->table) as $foreignKey) {
            foreach ($foreignKey->source as $val) {
                $selectDto->foreignKeys[$val][] = $foreignKey;
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
                in_array($value['fun'], $this->driver->functions()) ||
                in_array($value['fun'], $this->driver->grouping())));
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
     * @param SelectDto $selectDto
     *
     * @return void
     */
    private function setSelectColumns(SelectDto $selectDto): void
    {
        $selectDto->select = []; // select expressions, empty for *
        $selectDto->group = []; // expressions without aggregation - will be used for GROUP BY if an aggregation function is used
        $values = $this->utils->input->values;
        foreach ($values['columns'] as $key => $value) {
            if ($this->colHasValidValue($value)) {
                $column = '*';
                if ($value['col'] !== '') {
                    $column = $this->driver->escapeId($value['col']);
                }
                $selectDto->select[$key] = $this->page->applySqlFunction($value['fun'], $column);
                if (!in_array($value['fun'], $this->driver->grouping())) {
                    $selectDto->group[] = $selectDto->select[$key];
                }
            }
        }
    }

    /**
     * @param array $value
     * @param array $fields
     *
     * @return string
     */
    private function getWhereCondition(array $value, array $fields): string
    {
        $op = $value['op'];
        $val = $value['val'];
        $col = $value['col'];

        return match(true) {
            preg_match('~IN$~', $op) > 0 => " $op " .
                (($in = $this->driver->processLength($val)) !== '' ? $in : '(NULL)'),
            $op === 'SQL' => " $val", // SQL injection
            $op === 'LIKE %%' => ' LIKE ' . $this->page
                ->getUnconvertedFieldValue($fields[$col], "%$val%"),
            $op === 'ILIKE %%' => ' ILIKE ' . $this->page
                ->getUnconvertedFieldValue($fields[$col], "%$val%"),
            $op === 'FIND_IN_SET' => ')',
            !preg_match('~NULL$~', $op) => " $op " .
                $this->page->getUnconvertedFieldValue($fields[$col], $val),
            default => " $op",
        };
    }

    /**
     * @param TableFieldDto $field
     * @param array $value
     *
     * @return bool
     */
    private function selectFieldIsValid(TableFieldDto $field, array $value): bool
    {
        $op = $value['op'];
        $val = $value['val'];
        $in = preg_match('~IN$~', $op) ? ',' : '';

        return (preg_match('~^[-\d.' . $in . ']+$~', $val) ||
                !preg_match('~' . $this->driver->numberRegex() . '|bit~', $field->type)) &&
            (!preg_match("~[\x80-\xFF]~", $val) ||
                preg_match('~char|text|enum|set~', $field->type)) &&
            (!preg_match('~date|timestamp~', $field->type) ||
                preg_match('~^\d+-\d+-\d+~', $val));
    }

    /**
     * @param array $value
     * @param array $fields
     *
     * @return string
     */
    private function getSelectExpression(array $value, array $fields): string
    {
        $op = $value['op'];
        $col = $value['col'];
        $prefix = '';
        if ($op === 'FIND_IN_SET') {
            $prefix = $op .'(' . $this->driver->quote($value['val']) . ', ';
        }
        $condition = $this->getWhereCondition($value, $fields);
        if ($col !== '') {
            return $prefix . $this->driver->convertSearch($this->driver->escapeId($col),
                    $value, $fields[$col]) . $condition;
        }
        // find anywhere
        $clauses = [];
        foreach ($fields as $name => $field) {
            if ($this->selectFieldIsValid($field, $value)) {
                $clauses[] = $prefix . $this->driver->convertSearch($this->driver->escapeId($name),
                        $value, $field) . $condition;
            }
        }

        return empty($clauses) ? '1 = 0' : ('(' . implode(' OR ', $clauses) . ')');
    }

    /**
     * @param IndexDto $index
     * @param int $i
     *
     * @return string
     */
    private function getMatchExpression(IndexDto $index, int $i): string
    {
        $columns = array_map(function ($column) {
            return $this->driver->escapeId($column);
        }, $index->columns);
        $fulltext = $this->utils->input->values['fulltext'][$i] ?? '';
        $match = $this->driver->quote($fulltext);
        if (isset($this->utils->input->values['boolean'][$i])) {
            $match .= ' IN BOOLEAN MODE';
        }

        return 'MATCH (' . implode(', ', $columns) . ') AGAINST (' . $match . ')';
    }

    /**
     * @param SelectDto $selectDto
     *
     * @return void
     */
    private function setSelectWhere(SelectDto $selectDto): void
    {
        $selectDto->where = [];
        foreach ($selectDto->indexes as $i => $index) {
            $fulltext = $this->utils->input->values['fulltext'][$i] ?? '';
            if ($index->type === 'FULLTEXT' && $fulltext !== '') {
                $selectDto->where[] = $this->getMatchExpression($index, $i);
            }
        }
        foreach ((array) $this->utils->input->values['where'] as $value) {
            if (($value['col'] !== '' ||  $value['val'] !== '') &&
                in_array($value['op'], $this->driver->operators())) {
                $selectDto->where[] = $this
                    ->getSelectExpression($value, $selectDto->fields);
            }
        }
    }

    /**
     * @param SelectDto $selectDto
     *
     * @return void
     */
    private function setSelectOrder(SelectDto $selectDto): void
    {
        $values = $this->utils->input->values;
        $selectDto->order = [];
        foreach ($values['order'] as $key => $value) {
            if ($value !== '') {
                $regexp = '~^((COUNT\(DISTINCT |[A-Z0-9_]+\()(`(?:[^`]|``)+`|"(?:[^"]|"")+")\)|COUNT\(\*\))$~';
                if (preg_match($regexp, $value) !== false) {
                    $value = $this->driver->escapeId($value);
                }
                if (isset($values['desc'][$key]) && intval($values['desc'][$key]) !== 0) {
                    $value .= ' DESC';
                }
                $selectDto->order[] = $value;
            }
        }
    }

    /**
     * @param SelectDto $selectDto
     *
     * @return void
     */
    private function setSelectLimit(SelectDto $selectDto): void
    {
        $selectDto->limit = intval($this->utils->input->values['limit'] ?? 50);
    }

    /**
     * @param SelectDto $selectDto
     *
     * @return void
     */
    private function setSelectTextLength(SelectDto $selectDto): void
    {
        $selectDto->textLength = intval($this->utils->input->values['length'] ?? 100);
    }

    /**
     * @param SelectDto $selectDto
     *
     * @return void
     */
    private function setPrimaryKey(SelectDto $selectDto): void
    {
        $primary = null;
        $selectDto->unselected = [];
        foreach ($selectDto->indexes as $index) {
            if ($index->type === "PRIMARY") {
                $primary = array_flip($index->columns);
                $selectDto->unselected = ($selectDto->select ? $primary : []);
                foreach ($selectDto->unselected as $key => $val) {
                    if (in_array($this->driver->escapeId($key), $selectDto->select)) {
                        unset($selectDto->unselected[$key]);
                    }
                }
                break;
            }
        }

        $oid = $selectDto->tableStatus->oid;
        if ($oid && !$primary) {
            /*$primary = */$selectDto->unselected = [$oid => 0];
            // Make an index for the OID
            $index = new IndexDto();
            $index->type = "PRIMARY";
            $index->columns = [$oid];
            $selectDto->indexes[] = $index;
        }
    }

    /**
     * @param SelectDto $selectDto
     *
     * @return void
     */
    private function setSelectDto(SelectDto $selectDto): void
    {
        $select2 = $selectDto->select;
        $group2 = $selectDto->group;
        if (empty($select2)) {
            $select2[] = "*";
            $convert_fields = $this->driver->convertFields($selectDto->columns,
                $selectDto->fields, $selectDto->select);
            if ($convert_fields) {
                $select2[] = substr($convert_fields, 2);
            }
        }
        foreach ($selectDto->select as $key => $val) {
            $field = $fields[$this->driver->unescapeId($val)] ?? null;
            if ($field && ($as = $this->driver->convertField($field))) {
                $select2[$key] = "$as AS $val";
            }
        }
        $isGroup = count($selectDto->group) < count($selectDto->select);
        if (!$isGroup && !empty($unselected)) {
            foreach ($unselected as $key => $val) {
                $select2[] = $this->driver->escapeId($key);
                if (!empty($group2)) {
                    $group2[] = $this->driver->escapeId($key);
                }
            }
        }

        // From driver.inc.php
        $selectDto->tableSelect = new TableSelectDto($selectDto->table,
            $select2, $selectDto->where, $group2, $selectDto->order,
            $selectDto->limit, $selectDto->page);
    }

    /**
     * Get required data for select on tables
     *
     * @param SelectDto $selectDto
     *
     * @return SelectDto
     * @throws Exception
     */
    public function prepareSelect(SelectDto $selectDto): SelectDto
    {
        $this->selectOptions->setDefaultOptions($selectDto);

        // From select.inc.php
        $selectDto->fields = $this->driver->fields($selectDto->table);
        $this->setFieldsOptions($selectDto);
        if (!$selectDto->columns && $this->driver->support("table")) {
            throw new Exception($this->utils->trans->lang('Unable to select the table') .
                ($selectDto->fields ? "." : ": " . $this->driver->error()));
        }

        $selectDto->indexes = $this->driver->indexes($selectDto->table);
        $this->setForeignKeys($selectDto);
        $this->setSelectColumns($selectDto);

        $this->setSelectWhere($selectDto);
        $this->setSelectOrder($selectDto);
        $this->setSelectLimit($selectDto);
        $this->setPrimaryKey($selectDto);

        // $set = null;
        // if(isset($rights["insert"]) || !this->driver->support("table")) {
        //     $set = "";
        //     foreach((array) $queryOptions["where"] as $val) {
        //         if($foreignKeys[$val["col"]] && count($foreignKeys[$val["col"]]) == 1 && ($val["op"] == "="
        //             || (!$val["op"] && !preg_match('~[_%]~', $val["val"])) // LIKE in Editor
        //         )) {
        //             $set .= "&set" . urlencode("[" . $this->driver->bracketEscape($val["col"]) . "]") . "=" . urlencode($val["val"]);
        //         }
        //     }
        // }
        // $this->page->selectLinks($tableStatus, $set);

        // if($page == "last")
        // {
        //     $isGroup = count($group) < count($select);
        //     $found_rows = $this->driver->result($this->driver->getRowCountQuery($table, $where, $isGroup, $group));
        //     $page = \floor(\max(0, $found_rows - 1) / $limit);
        // }

        $this->selectOptions->setSelectOptions($selectDto);
        $this->setSelectDto($selectDto);

        $query = $this->driver->buildSelectQuery($selectDto->tableSelect);
        // From adminer.inc.php
        $selectDto->query = str_replace("\n", " ", $query);

        return $selectDto;
    }
}
