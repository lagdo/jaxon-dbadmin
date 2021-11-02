<?php

namespace Lagdo\DbAdmin\DbAdmin;

use Lagdo\DbAdmin\Driver\Entity\TableFieldEntity;
use Lagdo\DbAdmin\Driver\Entity\TableSelectEntity;

use Exception;

/**
 * Admin table select functions
 */
class TableSelectAdmin extends AbstractAdmin
{
    /**
     * Print columns box in select
     * @param array result of processSelectColumns()[0]
     * @param array selectable columns
     * @param array $options
     * @return array
     */
    private function getColumnsOptions(array $select, array $columns, array $options)
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
     * @param array result of processSelectSearch()
     * @param array selectable columns
     * @param array $indexes
     * @param array $options
     * @return array
     */
    private function getFiltersOptions(array $where, array $columns, array $indexes, array $options)
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
     * @param array result of processSelectOrder()
     * @param array selectable columns
     * @param array $indexes
     * @param array $options
     * @return array
     */
    private function getSortingOptions(array $order, array $columns, array $indexes, array $options)
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
     * @param string result of processSelectLimit()
     * @return array
     */
    private function getLimitOptions(string $limit)
    {
        return ['value' => $this->util->html($limit)];
    }

    /**
     * Print text length box in select
     * @param string|null result of processSelectLength()
     * @return array
     */
    private function getLengthOptions($textLength)
    {
        return [
            'value' => $textLength === null ? 0 : $this->util->html($textLength),
        ];
    }

    /**
     * Print action box in select
     * @param array
     * @return array
     */
    private function getActionOptions(array $indexes)
    {
        $columns = [];
        foreach ($indexes as $index) {
            $current_key = \reset($index->columns);
            if ($index->type != "FULLTEXT" && $current_key) {
                $columns[$current_key] = 1;
            }
        }
        $columns[""] = 1;
        return ['columns' => $columns];
    }

    /**
     * Print command box in select
     * @return bool whether to print default commands
     */
    private function getCommandOptions()
    {
        return !$this->driver->isInformationSchema(DB);
    }

    /**
     * Print import box in select
     * @return bool whether to print default import
     */
    private function getImportOptions()
    {
        return !$this->driver->isInformationSchema(DB);
    }

    /**
     * Print extra text in the end of a select form
     * @param array fields holding e-mails
     * @param array selectable columns
     * @return array
     */
    private function getEmailOptions($emailFields, $columns)
    {
    }

    /**
     * Get required data for create/update on tables
     *
     * @param string $table         The table name
     * @param array  $queryOptions  The query options
     *
     * @return array
     */
    private function prepareSelect(string $table, array &$queryOptions = [])
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
        $page = \intval($queryOptions['page']);
        if ($page > 0) {
            $page -= 1; // Page numbers start at 0 here, instead of 1.
        }
        $queryOptions['page'] = $page;

        $this->util->input()->values = $queryOptions;

        // From select.inc.php
        $indexes = $this->driver->indexes($table);
        $fields = $this->driver->fields($table);
        $foreignKeys = $this->admin->columnForeignKeys($table);

        $rights = []; // privilege => 0
        $columns = []; // selectable columns
        $textLength = null;
        foreach ($fields as $key => $field) {
            $name = $this->util->fieldName($field);
            if (isset($field->privileges["select"]) && $name != "") {
                $columns[$key] = \html_entity_decode(\strip_tags($name), ENT_QUOTES);
                if ($this->util->isShortable($field)) {
                    $textLength = $this->util->processSelectLength();
                }
            }
            $rights[] = $field->privileges;
        }

        list($select, $group) = $this->util->processSelectColumns($columns, $indexes);
        $isGroup = \count($group) < \count($select);
        $where = $this->util->processSelectSearch($fields, $indexes);
        $order = $this->util->processSelectOrder($fields, $indexes);
        $limit = $this->util->processSelectLimit();

        // if(isset($queryOptions["val"]) && is_ajax()) {
        //     header("Content-Type: text/plain; charset=utf-8");
        //     foreach($queryOptions["val"] as $unique_idf => $row) {
        //         $as = convertField($fields[key($row)]);
        //         $select = array($as ? $as : escapeId(key($row)));
        //         $where[] = where_check($unique_idf, $fields);
        //         $statement = $this->driver->select($table, $select, $where, $select);
        //         if($statement) {
        //             echo reset($statement->fetchRow());
        //         }
        //     }
        //     exit;
        // }

        $primary = $unselected = null;
        foreach ($indexes as $index) {
            if ($index->type == "PRIMARY") {
                $primary = \array_flip($index->columns);
                $unselected = ($select ? $primary : []);
                foreach ($unselected as $key => $val) {
                    if (\in_array($this->driver->escapeId($key), $select)) {
                        unset($unselected[$key]);
                    }
                }
                break;
            }
        }

        $tableStatus = $this->driver->tableStatusOrName($table);
        $oid = $tableStatus->oid;
        if ($oid && !$primary) {
            $primary = $unselected = [$oid => 0];
            $indexes[] = ["type" => "PRIMARY", "columns" => [$oid]];
        }

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

        if (!$columns && $this->driver->support("table")) {
            throw new Exception($this->trans->lang('Unable to select the table') .
                ($fields ? "." : ": " . $this->driver->error()));
        }

        // if($page == "last")
        // {
        //     $found_rows = $this->driver->result($this->driver->countRowsSql($table, $where, $isGroup, $group));
        //     $page = \floor(\max(0, $found_rows - 1) / $limit);
        // }

        $options = [
            'columns' => $this->getColumnsOptions($select, $columns, $queryOptions),
            'filters' => $this->getFiltersOptions($where, $columns, $indexes, $queryOptions),
            'sorting' => $this->getSortingOptions($order, $columns, $indexes, $queryOptions),
            'limit' => $this->getLimitOptions($limit),
            'length' => $this->getLengthOptions($textLength),
            // 'action' => $this->getActionOptions($indexes),
        ];

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
        if (!$isGroup && $unselected) {
            foreach ($unselected as $key => $val) {
                $select2[] = $this->driver->escapeId($key);
                if ($group2) {
                    $group2[] = $this->driver->escapeId($key);
                }
            }
        }

        // From driver.inc.php
        $entity = new TableSelectEntity($table, $select2, $where, $group2, $order, $limit, $page);
        $query = $this->driver->buildSelectQuery($entity);
        // From adminer.inc.php
        $query = \str_replace("\n", " ", $query);

        return [$options, $query, $select, $fields, $foreignKeys, $columns, $indexes,
            $where, $group, $order, $limit, $page, $textLength, $isGroup, $tableName];
    }

    /**
     * Get required data for create/update on tables
     *
     * @param string $table         The table name
     * @param array  $queryOptions  The query options
     *
     * @return array
     */
    public function getSelectData(string $table, array $queryOptions = [])
    {
        list($options, $query) = $this->prepareSelect($table, $queryOptions);

        $query = $this->util->html($query);
        $mainActions = [
            'select-exec' => $this->trans->lang('Execute'),
            'insert-table' => $this->trans->lang('New item'),
            'select-cancel' => $this->trans->lang('Cancel'),
        ];

        return \compact('mainActions', 'options', 'query');
    }

    /**
     * Get required data for create/update on tables
     *
     * @param string $table         The table name
     * @param array  $queryOptions  The query options
     *
     * @return array
     */
    public function execSelect(string $table, array $queryOptions)
    {
        list($options, $query, $select, $fields, $foreignKeys, $columns, $indexes,
            $where, $group, $order, $limit, $page, $textLength, $isGroup, $tableName) =
            $this->prepareSelect($table, $queryOptions);

        $error = null;
        // From driver.inc.php
        $start = microtime(true);
        $statement = $this->driver->query($query);
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
        if (!$rows) {
            return ['error' => $this->trans->lang('No rows.')];
        }
        // $backward_keys = $this->driver->backwardKeys($table, $tableName);

        // Results headers
        $headers = [
            '', // !$group && $select ? '' : lang('Modify');
        ];
        $names = [];
        $functions = [];
        reset($select);
        $rank = 1;
        foreach ($rows[0] as $key => $value) {
            $header = [];
            if (!isset($unselected[$key])) {
                $value = $queryOptions["columns"][key($select)] ?? [];
                $fun = $value["fun"] ?? '';
                $field = $fields[$select ? ($value ? $value["col"] : current($select)) : $key];
                $name = ($field ? $this->util->fieldName($field, $rank) : ($fun ? "*" : $key));
                $header = \compact('value', 'field', 'name');
                if ($name != "") {
                    $rank++;
                    $names[$key] = $name;
                    $column = $this->driver->escapeId($key);
                    // $href = remove_from_uri('(order|desc)[^=]*|page') . '&order%5B0%5D=' . urlencode($key);
                    // $desc = "&desc%5B0%5D=1";
                    $header['column'] = $column;
                    $header['key'] = $this->util->html($this->util->bracketEscape($key));
                    $header['sql'] = $this->admin->applySqlFunction($fun, $name); //! columns looking like functions
                }
                $functions[$key] = $fun;
                next($select);
            }
            $headers[] = $header;
        }

        // $lengths = [];
        // if($queryOptions["modify"])
        // {
        //     foreach($rows as $row)
        //     {
        //         foreach($row as $key => $value)
        //         {
        //             $lengths[$key] = \max($lengths[$key], \min(40, strlen(\utf8_decode($value))));
        //         }
        //     }
        // }

        $results = [];
        foreach ($rows as $n => $row) {
            $uniqueIds = $this->util->uniqueIds($rows[$n], $indexes);
            if (empty($uniqueIds)) {
                foreach ($rows[$n] as $key => $value) {
                    if (!\preg_match('~^(COUNT\((\*|(DISTINCT )?`(?:[^`]|``)+`)\)|(AVG|GROUP_CONCAT|MAX|MIN|SUM)\(`(?:[^`]|``)+`\))$~', $key)) {
                        //! columns looking like functions
                        $uniqueIds[$key] = $value;
                    }
                }
            }

            // Unique identifier to edit returned data.
            // $unique_idf = "";
            $rowIds = [
                'where' => [],
                'null' => [],
            ];
            foreach ($uniqueIds as $key => $value) {
                $key = \trim($key);
                $type = '';
                $collation = '';
                if (isset($fields[$key])) {
                    $type = $fields[$key]->type;
                    $collation = $fields[$key]->collation;
                }
                if (($this->driver->jush() == "sql" || $this->driver->jush() == "pgsql") &&
                    \preg_match('~char|text|enum|set~', $type) && strlen($value) > 64) {
                    $key = (\strpos($key, '(') ? $key : $this->driver->escapeId($key)); //! columns looking like functions
                    $key = "MD5(" . ($this->driver->jush() != 'sql' || \preg_match("~^utf8~", $collation) ?
                        $key : "CONVERT($key USING " . $this->driver−>charset() . ")") . ")";
                    $value = \md5($value);
                }
                if ($value !== null) {
                    $rowIds['where'][$this->util->bracketEscape($key)] = $value;
                } else {
                    $rowIds['null'][] = $this->util->bracketEscape($key);
                }
                // $unique_idf .= "&" . ($value !== null ? \urlencode("where[" . $this->util->bracketEscape($key) . "]") .
                //     "=" . \urlencode($value) : \urlencode("null[]") . "=" . \urlencode($key));
            }

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

                    $value = $this->util->selectValue($value ?? '', $link, $field, $textLength);
                    $text = \preg_match('~text|lob~', $field->type);

                    $cols[] = \compact(/*'id', */'text', 'value'/*, 'editable'*/);
                }
            }
            $results[] = ['ids' => $rowIds, 'cols' => $cols];
        }

        $total = $this->driver->result($this->driver->countRowsSql($table, $where, $isGroup, $group));

        $rows = $results;
        return \compact('duration', 'headers', 'query', 'rows', 'limit', 'total', 'error');
    }
}
