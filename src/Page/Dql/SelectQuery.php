<?php

namespace Lagdo\DbAdmin\Db\Page\Dql;

use Lagdo\DbAdmin\Db\Page\AppPage;
use Lagdo\DbAdmin\Driver\DriverInterface;
use Lagdo\DbAdmin\Driver\Entity\IndexEntity;
use Lagdo\DbAdmin\Driver\Entity\TableFieldEntity;
use Lagdo\DbAdmin\Driver\Entity\TableSelectEntity;
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
     * @param SelectEntity $selectEntity
     *
     * @return void
     */
    private function setFieldsOptions(SelectEntity $selectEntity): void
    {
        $selectEntity->rights = []; // privilege => 0
        $selectEntity->columns = []; // selectable columns
        $selectEntity->textLength = 0;
        foreach ($selectEntity->fields as $key => $field) {
            $name = $this->page->fieldName($field);
            if (isset($field->privileges["select"]) && $name != "") {
                $selectEntity->columns[$key] = html_entity_decode(strip_tags($name), ENT_QUOTES);
                if ($this->page->isShortable($field)) {
                    $this->setSelectTextLength($selectEntity);
                }
            }
            $selectEntity->rights[] = $field->privileges;
        }
    }

    /**
     * Find out foreign keys for each column
     *
     * @param SelectEntity $selectEntity
     *
     * @return void
     */
    private function setForeignKeys(SelectEntity $selectEntity): void
    {
        $selectEntity->foreignKeys = [];
        foreach ($this->driver->foreignKeys($selectEntity->table) as $foreignKey) {
            foreach ($foreignKey->source as $val) {
                $selectEntity->foreignKeys[$val][] = $foreignKey;
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
     * @param SelectEntity $selectEntity
     *
     * @return void
     */
    private function setSelectColumns(SelectEntity $selectEntity): void
    {
        $selectEntity->select = []; // select expressions, empty for *
        $selectEntity->group = []; // expressions without aggregation - will be used for GROUP BY if an aggregation function is used
        $values = $this->utils->input->values;
        foreach ($values['columns'] as $key => $value) {
            if ($this->colHasValidValue($value)) {
                $column = '*';
                if ($value['col'] !== '') {
                    $column = $this->driver->escapeId($value['col']);
                }
                $selectEntity->select[$key] = $this->page->applySqlFunction($value['fun'], $column);
                if (!in_array($value['fun'], $this->driver->grouping())) {
                    $selectEntity->group[] = $selectEntity->select[$key];
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
     * @param TableFieldEntity $field
     * @param array $value
     *
     * @return bool
     */
    private function selectFieldIsValid(TableFieldEntity $field, array $value): bool
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
     * @param IndexEntity $index
     * @param int $i
     *
     * @return string
     */
    private function getMatchExpression(IndexEntity $index, int $i): string
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
     * @param SelectEntity $selectEntity
     *
     * @return void
     */
    private function setSelectWhere(SelectEntity $selectEntity): void
    {
        $selectEntity->where = [];
        foreach ($selectEntity->indexes as $i => $index) {
            $fulltext = $this->utils->input->values['fulltext'][$i] ?? '';
            if ($index->type === 'FULLTEXT' && $fulltext !== '') {
                $selectEntity->where[] = $this->getMatchExpression($index, $i);
            }
        }
        foreach ((array) $this->utils->input->values['where'] as $value) {
            if (($value['col'] !== '' ||  $value['val'] !== '') &&
                in_array($value['op'], $this->driver->operators())) {
                $selectEntity->where[] = $this
                    ->getSelectExpression($value, $selectEntity->fields);
            }
        }
    }

    /**
     * @param SelectEntity $selectEntity
     *
     * @return void
     */
    private function setSelectOrder(SelectEntity $selectEntity): void
    {
        $values = $this->utils->input->values;
        $selectEntity->order = [];
        foreach ($values['order'] as $key => $value) {
            if ($value !== '') {
                $regexp = '~^((COUNT\(DISTINCT |[A-Z0-9_]+\()(`(?:[^`]|``)+`|"(?:[^"]|"")+")\)|COUNT\(\*\))$~';
                if (preg_match($regexp, $value) !== false) {
                    $value = $this->driver->escapeId($value);
                }
                if (isset($values['desc'][$key]) && intval($values['desc'][$key]) !== 0) {
                    $value .= ' DESC';
                }
                $selectEntity->order[] = $value;
            }
        }
    }

    /**
     * @param SelectEntity $selectEntity
     *
     * @return void
     */
    private function setSelectLimit(SelectEntity $selectEntity): void
    {
        $selectEntity->limit = intval($this->utils->input->values['limit'] ?? 50);
    }

    /**
     * @param SelectEntity $selectEntity
     *
     * @return void
     */
    private function setSelectTextLength(SelectEntity $selectEntity): void
    {
        $selectEntity->textLength = intval($this->utils->input->values['text_length'] ?? 100);
    }

    /**
     * @param SelectEntity $selectEntity
     *
     * @return void
     */
    private function setPrimaryKey(SelectEntity $selectEntity): void
    {
        $primary = null;
        $selectEntity->unselected = [];
        foreach ($selectEntity->indexes as $index) {
            if ($index->type === "PRIMARY") {
                $primary = array_flip($index->columns);
                $selectEntity->unselected = ($selectEntity->select ? $primary : []);
                foreach ($selectEntity->unselected as $key => $val) {
                    if (in_array($this->driver->escapeId($key), $selectEntity->select)) {
                        unset($selectEntity->unselected[$key]);
                    }
                }
                break;
            }
        }

        $oid = $selectEntity->tableStatus->oid;
        if ($oid && !$primary) {
            /*$primary = */$selectEntity->unselected = [$oid => 0];
            // Make an index for the OID
            $index = new IndexEntity();
            $index->type = "PRIMARY";
            $index->columns = [$oid];
            $selectEntity->indexes[] = $index;
        }
    }

    /**
     * @param SelectEntity $selectEntity
     *
     * @return void
     */
    private function setSelectEntity(SelectEntity $selectEntity): void
    {
        $select2 = $selectEntity->select;
        $group2 = $selectEntity->group;
        if (empty($select2)) {
            $select2[] = "*";
            $convert_fields = $this->driver->convertFields($selectEntity->columns,
                $selectEntity->fields, $selectEntity->select);
            if ($convert_fields) {
                $select2[] = substr($convert_fields, 2);
            }
        }
        foreach ($selectEntity->select as $key => $val) {
            $field = $fields[$this->driver->unescapeId($val)] ?? null;
            if ($field && ($as = $this->driver->convertField($field))) {
                $select2[$key] = "$as AS $val";
            }
        }
        $isGroup = count($selectEntity->group) < count($selectEntity->select);
        if (!$isGroup && !empty($unselected)) {
            foreach ($unselected as $key => $val) {
                $select2[] = $this->driver->escapeId($key);
                if (!empty($group2)) {
                    $group2[] = $this->driver->escapeId($key);
                }
            }
        }

        // From driver.inc.php
        $selectEntity->tableSelect = new TableSelectEntity($selectEntity->table,
            $select2, $selectEntity->where, $group2, $selectEntity->order,
            $selectEntity->limit, $selectEntity->page);
    }

    /**
     * Get required data for select on tables
     *
     * @param SelectEntity $selectEntity
     *
     * @return SelectEntity
     * @throws Exception
     */
    public function prepareSelect(SelectEntity $selectEntity): SelectEntity
    {
        $this->selectOptions->setDefaultOptions($selectEntity);

        // From select.inc.php
        $selectEntity->fields = $this->driver->fields($selectEntity->table);
        $this->setFieldsOptions($selectEntity);
        if (!$selectEntity->columns && $this->driver->support("table")) {
            throw new Exception($this->utils->trans->lang('Unable to select the table') .
                ($selectEntity->fields ? "." : ": " . $this->driver->error()));
        }

        $selectEntity->indexes = $this->driver->indexes($selectEntity->table);
        $this->setForeignKeys($selectEntity);
        $this->setSelectColumns($selectEntity);

        $this->setSelectWhere($selectEntity);
        $this->setSelectOrder($selectEntity);
        $this->setSelectLimit($selectEntity);
        $this->setPrimaryKey($selectEntity);

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

        $this->selectOptions->setSelectOptions($selectEntity);
        $this->setSelectEntity($selectEntity);

        $query = $this->driver->buildSelectQuery($selectEntity->tableSelect);
        // From adminer.inc.php
        $selectEntity->query = str_replace("\n", " ", $query);

        return $selectEntity;
    }
}
