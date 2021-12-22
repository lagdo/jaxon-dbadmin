<?php

namespace Lagdo\DbAdmin\DbAdmin\Traits;

use Lagdo\DbAdmin\Driver\Entity\TableSelectEntity;

use function intval;
use function count;
use function html_entity_decode;
use function strip_tags;
use function array_flip;
use function in_array;
use function substr;

trait TableSelectTrait
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
    private function getLengthOptions(int $textLength): array
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
     * @return array
     */
    private function setPrimaryKey(array &$indexes, array $select, $tableStatus): array
    {
        $primary = null;
        $unselected = [];
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
        if (empty($select2)) {
            $select2[] = "*";
            $convert_fields = $this->driver->convertFields($columns, $fields, $select);
            if ($convert_fields) {
                $select2[] = substr($convert_fields, 2);
            }
        }
        foreach ($select as $key => $val) {
            $field = $fields[$this->driver->unescapeId($val)] ?? null;
            if ($field && ($as = $this->driver->convertField($field))) {
                $select2[$key] = "$as AS $val";
            }
        }
        $isGroup = count($group) < count($select);
        if (!$isGroup && !empty($unselected)) {
            foreach ($unselected as $key => $val) {
                $select2[] = $this->driver->escapeId($key);
                if (!empty($group2)) {
                    $group2[] = $this->driver->escapeId($key);
                }
            }
        }

        // From driver.inc.php
        return new TableSelectEntity($table, $select2, $where, $group2, $order, $limit, $page);
    }
}
