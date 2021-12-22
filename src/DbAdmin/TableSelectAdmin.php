<?php

namespace Lagdo\DbAdmin\DbAdmin;

use Lagdo\DbAdmin\Driver\Entity\TableFieldEntity;
use Lagdo\DbAdmin\Driver\Entity\TableSelectEntity;

use Exception;

use function intval;
use function count;
use function str_replace;
use function compact;
use function preg_match;
use function microtime;
use function html_entity_decode;
use function strip_tags;
use function array_flip;
use function in_array;
use function trim;
use function md5;
use function strlen;
use function strpos;

/**
 * Admin table select functions
 */
class TableSelectAdmin extends AbstractAdmin
{
    /**
     * Print columns box in select
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
            $fulltexts[$i] = $index->type == "FULLTEXT" ? $this->util->html($options["fulltext"][$i]) : '';
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
        return ['value' => $this->util->html($limit)];
    }

    /**
     * Print text length box in select
     *
     * @param int $textLength Result of processSelectLength()
     *
     * @return array
     */
    private function getLengthOptions($textLength): array
    {
        return [
            'value' => $textLength === 0 ? 0 : $this->util->html($textLength),
        ];
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
     * @param array $queryOptions
     *
     * @return int
     */
    private function setDefaultOptions(array &$queryOptions): int
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
            if (!isset($queryOptions[$name])) {
                $queryOptions[$name] = $value;
            }
        }
        $page = intval($queryOptions['page']);
        if ($page > 0) {
            $page -= 1; // Page numbers start at 0 here, instead of 1.
        }
        $queryOptions['page'] = $page;
        return $page;
    }

    /**
     * @param array $fields
     *
     * @return array
     */
    private function getFieldsOptions(array $fields): array
    {
        $rights = []; // privilege => 0
        $columns = []; // selectable columns
        $textLength = 0;
        foreach ($fields as $key => $field) {
            $name = $this->util->fieldName($field);
            if (isset($field->privileges["select"]) && $name != "") {
                $columns[$key] = html_entity_decode(strip_tags($name), ENT_QUOTES);
                if ($this->util->isShortable($field)) {
                    $textLength = $this->util->processSelectLength();
                }
            }
            $rights[] = $field->privileges;
        }
        return [$rights, $columns, $textLength];
    }

    /**
     * @param array $indexes
     * @param array $select
     * @param mixed $tableStatus
     *
     * @return array|null
     */
    private function setPrimaryKey(array &$indexes, array $select, $tableStatus)
    {
        $primary = $unselected = null;
        foreach ($indexes as $index) {
            if ($index->type == "PRIMARY") {
                $primary = array_flip($index->columns);
                $unselected = ($select ? $primary : []);
                foreach ($unselected as $key => $val) {
                    if (in_array($this->driver->escapeId($key), $select)) {
                        unset($unselected[$key]);
                    }
                }
                break;
            }
        }

        $oid = $tableStatus->oid;
        if ($oid && !$primary) {
            /*$primary = */$unselected = [$oid => 0];
            $indexes[] = ["type" => "PRIMARY", "columns" => [$oid]];
        }

        return $unselected;
    }

    /**
     * @param array $select
     * @param array $columns
     * @param array $indexes
     * @param int $limit
     * @param int $textLength
     * @param array $queryOptions
     *
     * @return array
     */
    private function getAllOptions(array $select, array $columns, array $indexes,
        int $limit, int $textLength, array $queryOptions): array
    {
        return [
            'columns' => $this->getColumnsOptions($select, $columns, $queryOptions),
            'filters' => $this->getFiltersOptions($columns, $indexes, $queryOptions),
            'sorting' => $this->getSortingOptions($columns, $queryOptions),
            'limit' => $this->getLimitOptions($limit),
            'length' => $this->getLengthOptions($textLength),
            // 'action' => $this->getActionOptions($indexes),
        ];
    }

    /**
     * @param string $table
     * @param array $columns
     * @param array $fields
     * @param array $select
     * @param array $group
     * @param array $where
     * @param array $order
     * @param array $unselected
     * @param int $limit
     * @param int $page
     *
     * @return TableSelectEntity
     */
    private function getSelectEntity(string $table, array $columns, array $fields, array $select,
        array $group, array $where, array $order, array $unselected, int $limit, int $page): TableSelectEntity
    {
        $select2 = $select;
        $group2 = $group;
        if (!$select2) {
            $select2[] = "*";
            $convert_fields = $this->driver->convertFields($columns, $fields, $select);
            if ($convert_fields) {
                $select2[] = \substr($convert_fields, 2);
            }
        }
        foreach ($select as $key => $val) {
            $field = $fields[$this->driver->unescapeId($val)] ?? null;
            if ($field && ($as = $this->driver->convertField($field))) {
                $select2[$key] = "$as AS $val";
            }
        }
        $isGroup = count($group) < count($select);
        if (!$isGroup && $unselected) {
            foreach ($unselected as $key => $val) {
                $select2[] = $this->driver->escapeId($key);
                if ($group2) {
                    $group2[] = $this->driver->escapeId($key);
                }
            }
        }

        // From driver.inc.php
        return new TableSelectEntity($table, $select2, $where, $group2, $order, $limit, $page);
    }

    /**
     * Get required data for create/update on tables
     *
     * @param string $table The table name
     * @param array $queryOptions The query options
     *
     * @return array
     * @throws Exception
     */
    private function prepareSelect(string $table, array &$queryOptions = []): array
    {
        $page = $this->setDefaultOptions($queryOptions);
        $this->util->input()->setValues($queryOptions);

        // From select.inc.php
        $fields = $this->driver->fields($table);
        list(, $columns, $textLength) = $this->getFieldsOptions($fields);
        if (!$columns && $this->driver->support("table")) {
            throw new Exception($this->trans->lang('Unable to select the table') .
                ($fields ? "." : ": " . $this->driver->error()));
        }

        $indexes = $this->driver->indexes($table);
        $foreignKeys = $this->admin->columnForeignKeys($table);
        list($select, $group) = $this->util->processSelectColumns();
        $where = $this->util->processSelectSearch($fields, $indexes);
        $order = $this->util->processSelectOrder();
        $limit = $this->util->processSelectLimit();
        $tableStatus = $this->driver->tableStatusOrName($table);
        $unselected = $this->setPrimaryKey($indexes, $select, $tableStatus);
        $tableName = $this->util->tableName($tableStatus);

        // $set = null;
        // if(isset($rights["insert"]) || !support("table")) {
        //     $set = "";
        //     foreach((array) $queryOptions["where"] as $val) {
        //         if($foreignKeys[$val["col"]] && count($foreignKeys[$val["col"]]) == 1 && ($val["op"] == "="
        //             || (!$val["op"] && !preg_match('~[_%]~', $val["val"])) // LIKE in Editor
        //         )) {
        //             $set .= "&set" . urlencode("[" . $this->util->bracketEscape($val["col"]) . "]") . "=" . urlencode($val["val"]);
        //         }
        //     }
        // }
        // $this->util->selectLinks($tableStatus, $set);

        // if($page == "last")
        // {
        //     $isGroup = count($group) < count($select);
        //     $found_rows = $this->driver->result($this->driver->sqlForRowCount($table, $where, $isGroup, $group));
        //     $page = \floor(\max(0, $found_rows - 1) / $limit);
        // }

        $options = $this->getAllOptions($select, $columns, $indexes, $limit, $textLength, $queryOptions);
        $entity = $this->getSelectEntity($table, $columns, $fields, $select, $group, $where, $order, $unselected, $limit, $page);
        $query = $this->driver->buildSelectQuery($entity);
        // From adminer.inc.php
        $query = str_replace("\n", " ", $query);

        return [$options, $query, $select, $fields, $foreignKeys, $columns, $indexes,
            $where, $group, $order, $limit, $page, $textLength, $tableName, $unselected];
    }

    /**
     * Get required data for create/update on tables
     *
     * @param string $table The table name
     * @param array $queryOptions The query options
     *
     * @return array
     * @throws Exception
     */
    public function getSelectData(string $table, array $queryOptions = []): array
    {
        list($options, $query) = $this->prepareSelect($table, $queryOptions);

        $query = $this->util->html($query);
        $mainActions = [
            'select-exec' => $this->trans->lang('Execute'),
            'insert-table' => $this->trans->lang('New item'),
            'select-back' => $this->trans->lang('Back'),
        ];

        return compact('mainActions', 'options', 'query');
    }

    /**
     * @param string $query
     * @param int $page
     *
     * @return array
     */
    private function executeQuery(string $query, int $page): array
    {
        // From driver.inc.php
        $start = microtime(true);
        $statement = $this->driver->execute($query);
        // From adminer.inc.php
        $duration = $this->trans->formatTime($start); // Compute and format the duration

        if (!$statement) {
            return ['error' => $this->driver->error()];
        }
        // From select.inc.php
        $rows = [];
        while (($row = $statement->fetchAssoc())) {
            if ($page && $this->driver->jush() == "oracle") {
                unset($row["RNUM"]);
            }
            $rows[] = $row;
        }

        return [$rows, $duration];
    }

    /**
     * @param array $rows
     * @param array $select
     * @param array $fields
     * @param array $unselected
     *
     * @return array
     */
    private function getResultHeaders(array $rows, array $select, array $fields, array $unselected): array
    {
        // Results headers
        $headers = [
            '', // !$group && $select ? '' : lang('Modify');
        ];
        $names = [];
        // $functions = [];
        reset($select);
        $rank = 1;
        foreach ($rows[0] as $key => $value) {
            $header = [];
            if (!isset($unselected[$key])) {
                $value = $queryOptions["columns"][key($select)] ?? [];
                $fun = $value["fun"] ?? '';
                $field = $fields[$select ? ($value ? $value["col"] : current($select)) : $key];
                $name = ($field ? $this->util->fieldName($field, $rank) : ($fun ? "*" : $key));
                $header = compact('value', 'field', 'name');
                if ($name != "") {
                    $rank++;
                    $names[$key] = $name;
                    $column = $this->driver->escapeId($key);
                    // $href = remove_from_uri('(order|desc)[^=]*|page') . '&order%5B0%5D=' . urlencode($key);
                    // $desc = "&desc%5B0%5D=1";
                    $header['column'] = $column;
                    $header['key'] = $this->util->html($this->util->bracketEscape($key));
                    $header['sql'] = $this->util->applySqlFunction($fun, $name); //! columns looking like functions
                }
                // $functions[$key] = $fun;
                next($select);
            }
            $headers[] = $header;
        }
        return [$headers, $names];
    }

    /**
     * @param array $rows
     * @param array $queryOptions
     *
     * @return array
     */
    /*private function getValuesLengths(array $rows, array $queryOptions): array
    {
         $lengths = [];
         if($queryOptions["modify"])
         {
             foreach($rows as $row)
             {
                 foreach($row as $key => $value)
                 {
                     $lengths[$key] = \max($lengths[$key], \min(40, strlen(\utf8_decode($value))));
                 }
             }
         }
         return $lengths;
    }*/

    /**
     * @param array $row
     * @param array $indexes
     *
     * @return array
     */
    private function getUniqueIds(array $row, array $indexes): array
    {
        $uniqueIds = $this->util->uniqueIds($row, $indexes);
        if (empty($uniqueIds)) {
            $pattern = '~^(COUNT\((\*|(DISTINCT )?`(?:[^`]|``)+`)\)|(AVG|GROUP_CONCAT|MAX|MIN|SUM)\(`(?:[^`]|``)+`\))$~';
            foreach ($row as $key => $value) {
                if (!preg_match($pattern, $key)) {
                    //! columns looking like functions
                    $uniqueIds[$key] = $value;
                }
            }
        }
        return $uniqueIds;
    }

    /**
     * @param array $row
     * @param array $indexes
     *
     * @return array
     */
    private function getRowIds(array $row, array $indexes): array
    {
        $uniqueIds = $this->getUniqueIds($row, $indexes);
        // Unique identifier to edit returned data.
        // $unique_idf = "";
        $rowIds = ['where' => [], 'null' => []];
        foreach ($uniqueIds as $key => $value) {
            $key = trim($key);
            $type = '';
            $collation = '';
            if (isset($fields[$key])) {
                $type = $fields[$key]->type;
                $collation = $fields[$key]->collation;
            }
            if (($this->driver->jush() == "sql" || $this->driver->jush() == "pgsql") &&
                preg_match('~char|text|enum|set~', $type) && strlen($value) > 64) {
                $key = (strpos($key, '(') ? $key : $this->driver->escapeId($key)); //! columns looking like functions
                $key = "MD5(" . ($this->driver->jush() != 'sql' || preg_match("~^utf8~", $collation) ?
                        $key : "CONVERT($key USING " . $this->driver->charset() . ")") . ")";
                $value = md5($value);
            }
            if ($value !== null) {
                $rowIds['where'][$this->util->bracketEscape($key)] = $value;
            } else {
                $rowIds['null'][] = $this->util->bracketEscape($key);
            }
            // $unique_idf .= "&" . ($value !== null ? \urlencode("where[" . $this->util->bracketEscape($key) . "]") .
            //     "=" . \urlencode($value) : \urlencode("null[]") . "=" . \urlencode($key));
        }
        return $rowIds;
    }

    /**
     * @param array $row
     * @param array $names
     * @param int $textLength
     *
     * @return array
     */
    private function getRowColumns(array $row, array $names, int $textLength): array
    {
        $cols = [];
        foreach ($row as $key => $value) {
            if (isset($names[$key])) {
                $field = $fields[$key] ?? new TableFieldEntity();
                $value = $this->driver->value($value, $field);
                if ($value != "" && (!isset($email_fields[$key]) || $email_fields[$key] != "")) {
                    //! filled e-mails can be contained on other pages
                    $email_fields[$key] = ($this->util->isMail($value) ? $names[$key] : "");
                }

                $link = "";

                $value = $this->util->selectValue($value, $link, $field, $textLength);
                $text = preg_match('~text|lob~', $field->type);

                $cols[] = compact(/*'id', */'text', 'value'/*, 'editable'*/);
            }
        }
        return $cols;
    }

    /**
     * Get required data for create/update on tables
     *
     * @param string $table The table name
     * @param array $queryOptions The query options
     *
     * @return array
     * @throws Exception
     */
    public function execSelect(string $table, array $queryOptions): array
    {
        list(, $query, $select, $fields, , , $indexes, $where, $group, , $limit, $page,
            $textLength, , $unselected) = $this->prepareSelect($table, $queryOptions);

        list($rows, $duration) = $this->executeQuery($query, $page);
        if (!$rows) {
            return ['error' => $this->trans->lang('No rows.')];
        }
        // $backward_keys = $this->driver->backwardKeys($table, $tableName);
        // lengths = $this->getValuesLengths($rows, $queryOptions);

        list($headers, $names) = $this->getResultHeaders($rows, $select, $fields, $unselected);

        $results = [];
        foreach ($rows as $row) {
            // Unique identifier to edit returned data.
            $rowIds = $this->getRowIds($row, $indexes);
            $cols = $this->getRowColumns($row, $names, $textLength);
            $results[] = ['ids' => $rowIds, 'cols' => $cols];
        }

        $isGroup = count($group) < count($select);
        $total = $this->driver->result($this->driver->sqlForRowCount($table, $where, $isGroup, $group));

        $rows = $results;
        $error = null;
        return compact('duration', 'headers', 'query', 'rows', 'limit', 'total', 'error');
    }
}
