<?php

namespace Lagdo\DbAdmin\Db\Facades;

use Exception;
use Lagdo\DbAdmin\Driver\Entity\TableFieldEntity;

use function count;
use function str_replace;
use function compact;
use function preg_match;
use function microtime;
use function trim;
use function md5;
use function strlen;
use function strpos;

/**
 * Facade to table select functions
 */
class SelectFacade extends AbstractFacade
{
    use Traits\SelectTrait;

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
     * Find out foreign keys for each column
     *
     * @param string $table
     *
     * @return array
     */
    private function foreignKeys(string $table): array
    {
        $keys = [];
        foreach ($this->driver->foreignKeys($table) as $foreignKey) {
            foreach ($foreignKey->source as $val) {
                $keys[$val][] = $foreignKey;
            }
        }
        return $keys;
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
        $foreignKeys = $this->foreignKeys($table);
        list($select, $group) = $this->util->processSelectColumns();
        $where = $this->util->processSelectWhere($fields, $indexes);
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
    private function executeSelect(string $query, int $page): array
    {
        // From driver.inc.php
        $statement = $this->driver->execute($query);
        // From adminer.inc.php

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

        return [$rows, 0];
    }

    /**
     * @param array $rows
     * @param array $select
     * @param array $fields
     * @param array $unselected
     * @param array $queryOptions
     *
     * @return array
     */
    private function getResultHeaders(array $rows, array $select, array $fields, array $unselected, array $queryOptions): array
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
     * @param array $fields
     * @param array $indexes
     *
     * @return array
     */
    private function getRowIds(array $row, array $fields, array $indexes): array
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
     * @param array $fields
     * @param array $names
     * @param int $textLength
     *
     * @return array
     */
    private function getRowColumns(array $row, array $fields, array $names, int $textLength): array
    {
        $cols = [];
        foreach ($row as $key => $value) {
            if (isset($names[$key])) {
                $field = $fields[$key] ?? new TableFieldEntity();
                $value = $this->driver->value($value, $field);
                /*if ($value != "" && (!isset($email_fields[$key]) || $email_fields[$key] != "")) {
                    //! filled e-mails can be contained on other pages
                    $email_fields[$key] = ($this->util->isMail($value) ? $names[$key] : "");
                }*/
                $cols[] = [
                    // 'id',
                    'text' => preg_match('~text|lob~', $field->type),
                    'value' => $this->util->selectValue($field, $value, $textLength),
                    // 'editable' => false,
                ];
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

        list($rows, $duration) = $this->executeSelect($query, $page);
        if (!$rows) {
            return ['message' => $this->trans->lang('No rows.')];
        }
        // $backward_keys = $this->driver->backwardKeys($table, $tableName);
        // lengths = $this->getValuesLengths($rows, $queryOptions);

        list($headers, $names) = $this->getResultHeaders($rows, $select, $fields, $unselected, $queryOptions);

        $results = [];
        foreach ($rows as $row) {
            // Unique identifier to edit returned data.
            $rowIds = $this->getRowIds($row, $fields, $indexes);
            $cols = $this->getRowColumns($row, $fields, $names, $textLength);
            $results[] = ['ids' => $rowIds, 'cols' => $cols];
        }

        $isGroup = count($group) < count($select);
        $total = $this->driver->result($this->driver->sqlForRowCount($table, $where, $isGroup, $group));

        $rows = $results;
        $message = null;
        return compact('duration', 'headers', 'query', 'rows', 'limit', 'total', 'message');
    }
}
