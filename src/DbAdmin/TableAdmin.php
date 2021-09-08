<?php

namespace Lagdo\DbAdmin\DbAdmin;

use Lagdo\DbAdmin\Driver\Entity\TableField;

use Exception;

/**
 * Admin table functions
 */
class TableAdmin extends AbstractAdmin
{
    /**
     * The current table status
     *
     * @var mixed
     */
    protected $tableStatus = null;

    /**
     * Get the current table status
     *
     * @param string $table
     *
     * @return mixed
     */
    protected function status(string $table)
    {
        if (!$this->tableStatus) {
            $this->tableStatus = $this->db->tableStatusOrName($table, true);
        }
        return $this->tableStatus;
    }

    /**
     * Print links after select heading
     * Copied from selectLinks() in adminer.inc.php
     *
     * @param string new item options, NULL for no new item
     *
     * @return array
     */
    protected function getTableLinks($set = null)
    {
        $links = [
            'select' => $this->util->lang('Select data'),
        ];
        if ($this->db->support('table') || $this->db->support('indexes')) {
            $links['table'] = $this->util->lang('Show structure');
        }
        if ($this->db->support('table')) {
            $links['alter'] = $this->util->lang('Alter table');
        }
        if ($set !== null) {
            $links['edit'] = $this->util->lang('New item');
        }
        // $links['docs'] = \doc_link([$this->db->jush() => $this->db->tableHelp($name)], '?');

        return $links;
    }

    /**
     * Get details about a table
     *
     * @param string $table     The table name
     *
     * @return array
     */
    public function getTableInfo(string $table)
    {
        $mainActions = [
            'edit-table' => $this->util->lang('Alter table'),
            'drop-table' => $this->util->lang('Drop table'),
            'select-table' => $this->util->lang('Select'),
            'insert-table' => $this->util->lang('New item'),
        ];

        // From table.inc.php
        $status = $this->status($table);
        $name = $this->util->tableName($status);
        $title = $this->util->lang('Table') . ': ' . ($name != '' ? $name : $this->util->html($table));

        $comment = $status->comment;

        $tabs = [
            'fields' => $this->util->lang('Columns'),
            // 'indexes' => $this->util->lang('Indexes'),
            // 'foreign-keys' => $this->util->lang('Foreign keys'),
            // 'triggers' => $this->util->lang('Triggers'),
        ];
        if ($this->db->isView($status)) {
            if ($this->db->support('view_trigger')) {
                $tabs['triggers'] = $this->util->lang('Triggers');
            }
        } else {
            if ($this->db->support('indexes')) {
                $tabs['indexes'] = $this->util->lang('Indexes');
            }
            if ($this->db->supportForeignKeys($status)) {
                $tabs['foreign-keys'] = $this->util->lang('Foreign keys');
            }
            if ($this->db->support('trigger')) {
                $tabs['triggers'] = $this->util->lang('Triggers');
            }
        }

        return \compact('mainActions', 'title', 'comment', 'tabs');
    }

    /**
     * Get the fields of a table
     *
     * @param string $table     The table name
     *
     * @return array
     */
    public function getTableFields(string $table)
    {
        // From table.inc.php
        $fields = $this->db->fields($table);
        if (!$fields) {
            throw new Exception($this->util->error());
        }

        $mainActions = $this->getTableLinks();

        $tabs = [
            'fields' => $this->util->lang('Columns'),
            // 'indexes' => $this->util->lang('Indexes'),
            // 'foreign-keys' => $this->util->lang('Foreign keys'),
            // 'triggers' => $this->util->lang('Triggers'),
        ];
        if ($this->db->support('indexes')) {
            $tabs['indexes'] = $this->util->lang('Indexes');
        }
        if ($this->db->supportForeignKeys($this->status($table))) {
            $tabs['foreign-keys'] = $this->util->lang('Foreign keys');
        }
        if ($this->db->support('trigger')) {
            $tabs['triggers'] = $this->util->lang('Triggers');
        }

        $headers = [
            $this->util->lang('Name'),
            $this->util->lang('Type'),
            $this->util->lang('Collation'),
        ];
        $hasComment = $this->db->support('comment');
        if ($hasComment) {
            $headers[] = $this->util->lang('Comment');
        }

        $details = [];
        foreach ($fields as $field) {
            $type = $this->util->html($field->fullType);
            if ($field->null) {
                $type .= ' <i>nullable</i>'; // ' <i>NULL</i>';
            }
            if ($field->autoIncrement) {
                $type .= ' <i>' . $this->util->lang('Auto Increment') . '</i>';
            }
            if ($field->default !== '') {
                $type .= /*' ' . $this->util->lang('Default value') .*/ ' [<b>' . $this->util->html($field->default) . '</b>]';
            }
            $detail = [
                'name' => $this->util->html($field->name),
                'type' => $type,
                'collation' => $this->util->html($field->collation),
            ];
            if ($hasComment) {
                $detail['comment'] = $this->util->html($field->comment);
            }

            $details[] = $detail;
        }

        return \compact('mainActions', 'headers', 'details');
    }

    /**
     * Get the indexes of a table
     *
     * @param string $table     The table name
     *
     * @return array|null
     */
    public function getTableIndexes(string $table)
    {
        if (!$this->db->support('indexes')) {
            return null;
        }

        // From table.inc.php
        $indexes = $this->db->indexes($table);
        $mainActions = [
            'create' => $this->util->lang('Alter indexes'),
        ];

        $headers = [
            $this->util->lang('Name'),
            $this->util->lang('Type'),
            $this->util->lang('Column'),
        ];

        $details = [];
        // From adminer.inc.php
        if (!$indexes) {
            $indexes = [];
        }
        foreach ($indexes as $name => $index) {
            \ksort($index->columns); // enforce correct columns order
            $print = [];
            foreach ($index->columns as $key => $val) {
                $value = '<i>' . $this->util->html($val) . '</i>';
                if (\array_key_exists('lengths', $index) &&
                    \is_array($index->lengths) &&
                    \array_key_exists($key, $index->lengths)) {
                    $value .= '(' . $index->lengths[$key] . ')';
                }
                if (\array_key_exists('descs', $index) &&
                    \is_array($index->descs) &&
                    \array_key_exists($key, $index->descs)) {
                    $value .= ' DESC';
                }
                $print[] = $value;
            }
            $details[] = [
                'name' => $this->util->html($name),
                'type' => $index->type,
                'desc' => \implode(', ', $print),
            ];
        }

        return \compact('mainActions', 'headers', 'details');
    }

    /**
     * Get the foreign keys of a table
     *
     * @param string $table     The table name
     *
     * @return array|null
     */
    public function getTableForeignKeys(string $table)
    {
        $status = $this->status($table);
        if (!$this->db->supportForeignKeys($status)) {
            return null;
        }

        // From table.inc.php
        $mainActions = [
            $this->util->lang('Add foreign key'),
        ];

        $headers = [
            $this->util->lang('Name'),
            $this->util->lang('Source'),
            $this->util->lang('Target'),
            $this->util->lang('ON DELETE'),
            $this->util->lang('ON UPDATE'),
        ];

        $foreignKeys = $this->db->foreignKeys($table);
        if (!$foreignKeys) {
            $foreignKeys = [];
        }
        $details = [];
        // From table.inc.php
        foreach ($foreignKeys as $name => $foreignKey) {
            $target = '';
            if (\array_key_exists('db', $foreignKey) && $foreignKey->db != '') {
                $target .= '<b>' . $this->util->html($foreignKey->db) . '</b>.';
            }
            if (\array_key_exists('ns', $foreignKey) && $foreignKey->schema != '') {
                $target .= '<b>' . $this->util->html($foreignKey->schema) . '</b>.';
            }
            $target = $this->util->html($foreignKey->table) .
                '(' . \implode(', ', \array_map(function ($key) {
                    return $this->util->html($key);
                }, $foreignKey->target)) . ')';
            $details[] = [
                'name' => $this->util->html($name),
                'source' => '<i>' . \implode(
                    '</i>, <i>',
                    \array_map(function ($key) {
                        return $this->util->html($key);
                    }, $foreignKey->source)
                ) . '</i>',
                'target' => $target,
                'onDelete' => $this->util->html($foreignKey->onDelete),
                'onUpdate' => $this->util->html($foreignKey->onUpdate),
            ];
        }

        return \compact('mainActions', 'headers', 'details');
    }

    /**
     * Get the triggers of a table
     *
     * @param string $table     The table name
     *
     * @return array|null
     */
    public function getTableTriggers(string $table)
    {
        $status = $this->status($table);
        if (!$this->db->support('trigger')) {
            return null;
        }

        // From table.inc.php
        $triggers = $this->db->triggers($table);
        $mainActions = [
            $this->util->lang('Add trigger'),
        ];

        $headers = [
            $this->util->lang('Name'),
            '&nbsp;',
            '&nbsp;',
            '&nbsp;',
        ];

        if (!$triggers) {
            $triggers = [];
        }
        $details = [];
        // From table.inc.php
        foreach ($triggers as $key => $val) {
            $details[] = [
                $this->util->html($val[0]),
                $this->util->html($val[1]),
                $this->util->html($key),
                $this->util->lang('Alter'),
            ];
        }

        return \compact('mainActions', 'headers', 'details');
    }

    /**
     * Get foreign keys
     *
     * @param string $table     The table name
     *
     * @return void
     */
    private function getForeignKeys(string $table = '')
    {
        $this->referencableTables = $this->util->referencableTables($table);
        $this->foreignKeys = [];
        foreach ($this->referencableTables as $tableName => $field) {
            $name = \str_replace('`', '``', $tableName) .
                '`' . \str_replace('`', '``', $field->name);
            // not escapeId() - used in JS
            $this->foreignKeys[$name] = $tableName;
        }
    }

    /**
     * Get field types
     *
     * @param string $type  The type name
     *
     * @return array
     */
    public function getFieldTypes(string $type = '')
    {
        // From includes/editing.inc.php
        $extraTypes = [];
        if ($type && !$this->db->typeExists($type) &&
            !isset($this->foreignKeys[$type]) && !\in_array($type, $extraTypes)) {
            $extraTypes[] = $type;
        }
        if ($this->foreignKeys) {
            $this->db->setStructuredType($this->util->lang('Foreign keys'), $this->foreignKeys);
        }
        return \array_merge($extraTypes, $this->db->structuredTypes());
    }

    /**
     * Get required data for create/update on tables
     *
     * @param string $table The table name
     *
     * @return array
     */
    public function getTableData(string $table = '')
    {
        $mainActions = [
            'table-save' => $this->util->lang('Save'),
            'table-cancel' => $this->util->lang('Cancel'),
        ];

        // From create.inc.php
        $status = [];
        $fields = [];
        if ($table !== '') {
            $status = $this->db->tableStatus($table);
            if (!$status) {
                throw new Exception($this->util->lang('No tables.'));
            }
            $fields = $this->db->fields($table);
        }

        $this->getForeignKeys();

        $hasAutoIncrement = false;
        foreach ($fields as &$field) {
            $hasAutoIncrement = $hasAutoIncrement && $field->autoIncrement;
            $field->hasDefault = $field->default !== null;
            if (\preg_match('~^CURRENT_TIMESTAMP~i', $field->onUpdate)) {
                $field->onUpdate = 'CURRENT_TIMESTAMP';
            }

            $type = $field->type;
            $field->types = $this->getFieldTypes($type);
            $field->lengthRequired = !$field->length && \preg_match('~var(char|binary)$~', $type);
            $field->collationHidden = !\preg_match('~(char|text|enum|set)$~', $type);
            $field->unsignedHidden = !(!$type || \preg_match($this->db->numberRegex(), $type));
            $field->onUpdateHidden = !\preg_match('~timestamp|datetime~', $type);
            $field->onDeleteHidden = !\preg_match('~`~', $type);
        }
        $options = [
            'hasAutoIncrement' => $hasAutoIncrement,
            'onUpdate' => ['CURRENT_TIMESTAMP'],
            'onDelete' => $this->db->onActions(),
        ];

        $collations = $this->db->collations();
        $engines = $this->db->engines();
        $support = [
            'columns' => $this->db->support('columns'),
            'comment' => $this->db->support('comment'),
            'partitioning' => $this->db->support('partitioning'),
            'move_col' => $this->db->support('move_col'),
            'drop_col' => $this->db->support('drop_col'),
        ];

        $foreignKeys = $this->foreignKeys;
        $unsigned = $this->db->unsigned();
        // Give the var a better name
        $table = $status;
        return \compact(
            'mainActions',
            'table',
            'foreignKeys',
            'fields',
            'options',
            'collations',
            'engines',
            'support',
            'unsigned'
        );
    }

    /**
     * Get fields for a new column
     *
     * @return array
     */
    public function getTableField()
    {
        $this->getForeignKeys();
        $field = new TableField();
        $field->types = $this->getFieldTypes();
        return $field;
    }

    /**
     * Create or alter a table
     *
     * @param array  $values    The table values
     * @param string $table     The table name
     *
     * @return array
     */
    private function createOrAlterTable(
        array $values,
        string $table,
        array $origFields,
        array $tableStatus,
        string $engine,
        string $collation,
        $comment
    )
    {
        // From create.inc.php
        $values['fields'] = (array)$values['fields'];
        if ($values['autoIncrementCol']) {
            $values['fields'][$values['autoIncrementCol']]['autoIncrement'] = true;
        }

        $fields = [];
        $allFields = [];
        $useAllFields = false;
        $foreign = [];
        $origField = \reset($origFields);
        $after = ' FIRST';

        $this->getForeignKeys();

        foreach ($values['fields'] as $key => $field) {
            $foreignKey = $this->foreignKeys[$field['type']] ?? null;
            //! can collide with user defined type
            $typeField = ($foreignKey !== null ? $this->referencableTables[$foreignKey] : $field);
            // Originally, deleted fields have the "field" field set to an empty string.
            // But in our implementation, the "field" field is deleted.
            // if($field['name'] != '')
            if (isset($field['name']) && $field['name'] != '') {
                if (!isset($field['hasDefault'])) {
                    $field['default'] = null;
                }
                $field['autoIncrement'] = ($key == $values['autoIncrementCol']);
                $field["null"] = isset($field["null"]);

                $processField = $this->util->processField($field, $typeField);
                $allFields[] = [$field['orig'], $processField, $after];
                if (!$origField || $processField != $this->util->processField($origField, $origField)) {
                    $fields[] = [$field['orig'], $processField, $after];
                    if ($field['orig'] != '' || $after) {
                        $useAllFields = true;
                    }
                }
                if ($foreignKey !== null) {
                    $fkey = new ForeignKey();
                    $fkey->table = $this->foreignKeys[$field['type']];
                    $fkey->source = [$field['name']];
                    $fkey->target = [$typeField['field']];
                    $fkey->onDelete = $field['onDelete'];
                    $foreign[$this->db->escapeId($field['name'])] = ($table != '' && $this->db->jush() != 'sqlite' ? 'ADD' : ' ') .
                        $this->db->formatForeignKey($fkey);
                }
                $after = ' AFTER ' . $this->db->escapeId($field['name']);
            } elseif ($field['orig'] != '') {
                // A missing "name" field and a not empty "orig" field means the column is to be dropped.
                // We also append null in the array because the drivers code accesses field at position 1.
                $useAllFields = true;
                $fields[] = [$field['orig'], null];
            }
            if ($field['orig'] != '') {
                $origField = \next($origFields);
                if (!$origField) {
                    $after = '';
                }
            }
        }

        // For now, partitioning is not implemented
        $partitioning = '';
        // if($partition_by[$values['partition_by']])
        // {
        //     $partitions = [];
        //     if($values['partition_by'] == 'RANGE' || $values['partition_by'] == 'LIST')
        //     {
        //         foreach(\array_filter($values['partition_names']) as $key => $val)
        //         {
        //             $value = $values['partition_values'][$key];
        //             $partitions[] = "\n  PARTITION " . $this->db->escapeId($val) .
        //                 ' VALUES ' . ($values['partition_by'] == 'RANGE' ? 'LESS THAN' : 'IN') .
        //                 ($value != '' ? ' ($value)' : ' MAXVALUE'); //! SQL injection
        //         }
        //     }
        //     $partitioning .= "\nPARTITION BY $values[partition_by]($values[partition])" .
        //         ($partitions // $values['partition'] can be expression, not only column
        //         ? ' (' . \implode(',', $partitions) . "\n)"
        //         : ($values['partitions'] ? ' PARTITIONS ' . (+$values['partitions']) : '')
        //     );
        // }
        // elseif($this->db->support('partitioning') &&
        //     \preg_match('~partitioned~', $tableStatus->Create_options))
        // {
        //     $partitioning .= "\nREMOVE PARTITIONING";
        // }

        $name = \trim($values['name']);
        $autoIncrement = $this->util->number($this->util->input()->getAutoIncrementStep());
        if ($this->db->jush() == 'sqlite' && ($useAllFields || $foreign)) {
            $fields = $allFields;
        }

        $success = $this->db->alterTable(
            $table,
            $name,
            $fields,
            $foreign,
            $comment,
            $engine,
            $collation,
            $autoIncrement,
            $partitioning
        );

        $message = $table == '' ?
            $this->util->lang('Table has been created.') :
            $this->util->lang('Table has been altered.');

        $error = $this->util->error();

        // From functions.inc.php
        // queries_redirect(ME . (support('table') ? 'table=' : 'select=') . urlencode($name), $message, $redirect);

        return \compact('success', 'message', 'error');
    }

    /**
     * Create a table
     *
     * @param array  $values    The table values
     *
     * @return array
     */
    public function createTable(array $values)
    {
        $origFields = [];
        $tableStatus = [];

        $comment = $values['comment'] ?? null;
        $engine = $values['engine'] ?? '';
        $collation = $values['collation'] ?? '';

        return $this->createOrAlterTable(
            $values,
            '',
            $origFields,
            $tableStatus,
            $engine,
            $collation,
            $comment
        );
    }

    /**
     * Alter a table
     *
     * @param string $table     The table name
     * @param array  $values    The table values
     *
     * @return array
     */
    public function alterTable(string $table, array $values)
    {
        $origFields = $this->db->fields($table);
        $tableStatus = $this->db->tableStatus($table);
        if (!$tableStatus) {
            throw new Exception($this->util->lang('No tables.'));
        }

        $currComment = $tableStatus->comment;
        $currEngine = $tableStatus->engine;
        $currCollation = $tableStatus->collation;
        $comment = $values['comment'] != $currComment ? $values['comment'] : null;
        $engine = $values['engine'] != $currEngine ? $values['engine'] : '';
        $collation = $values['collation'] != $currCollation ? $values['collation'] : '';

        return $this->createOrAlterTable($values, $table, $origFields,
            $tableStatus, $engine, $collation, $comment);
    }

    /**
     * Drop a table
     *
     * @param string $table     The table name
     *
     * @return array
     */
    public function dropTable(string $table)
    {
        $success = $this->db->dropTables([$table]);

        $error = $this->util->error();

        $message = $this->util->lang('Table has been dropped.');

        return \compact('success', 'message', 'error');
    }
}
