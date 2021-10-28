<?php

namespace Lagdo\DbAdmin\DbAdmin;

use Lagdo\DbAdmin\Driver\Entity\TableFieldEntity;

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
            $this->tableStatus = $this->driver->tableStatusOrName($table, true);
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
            'select' => $this->trans->lang('Select data'),
        ];
        if ($this->driver->support('table') || $this->driver->support('indexes')) {
            $links['table'] = $this->trans->lang('Show structure');
        }
        if ($this->driver->support('table')) {
            $links['alter'] = $this->trans->lang('Alter table');
        }
        if ($set !== null) {
            $links['edit'] = $this->trans->lang('New item');
        }
        // $links['docs'] = \doc_link([$this->driver->jush() => $this->driver->tableHelp($name)], '?');

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
            'edit-table' => $this->trans->lang('Alter table'),
            'drop-table' => $this->trans->lang('Drop table'),
            'select-table' => $this->trans->lang('Select'),
            'insert-table' => $this->trans->lang('New item'),
        ];

        // From table.inc.php
        $status = $this->status($table);
        $name = $this->util->tableName($status);
        $title = $this->trans->lang('Table') . ': ' . ($name != '' ? $name : $this->util->html($table));

        $comment = $status->comment;

        $tabs = [
            'fields' => $this->trans->lang('Columns'),
            // 'indexes' => $this->trans->lang('Indexes'),
            // 'foreign-keys' => $this->trans->lang('Foreign keys'),
            // 'triggers' => $this->trans->lang('Triggers'),
        ];
        if ($this->driver->isView($status)) {
            if ($this->driver->support('view_trigger')) {
                $tabs['triggers'] = $this->trans->lang('Triggers');
            }
        } else {
            if ($this->driver->support('indexes')) {
                $tabs['indexes'] = $this->trans->lang('Indexes');
            }
            if ($this->driver->supportForeignKeys($status)) {
                $tabs['foreign-keys'] = $this->trans->lang('Foreign keys');
            }
            if ($this->driver->support('trigger')) {
                $tabs['triggers'] = $this->trans->lang('Triggers');
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
        $fields = $this->driver->fields($table);
        if (!$fields) {
            throw new Exception($this->driver->error());
        }

        $mainActions = $this->getTableLinks();

        $tabs = [
            'fields' => $this->trans->lang('Columns'),
            // 'indexes' => $this->trans->lang('Indexes'),
            // 'foreign-keys' => $this->trans->lang('Foreign keys'),
            // 'triggers' => $this->trans->lang('Triggers'),
        ];
        if ($this->driver->support('indexes')) {
            $tabs['indexes'] = $this->trans->lang('Indexes');
        }
        if ($this->driver->supportForeignKeys($this->status($table))) {
            $tabs['foreign-keys'] = $this->trans->lang('Foreign keys');
        }
        if ($this->driver->support('trigger')) {
            $tabs['triggers'] = $this->trans->lang('Triggers');
        }

        $headers = [
            $this->trans->lang('Name'),
            $this->trans->lang('Type'),
            $this->trans->lang('Collation'),
        ];
        $hasComment = $this->driver->support('comment');
        if ($hasComment) {
            $headers[] = $this->trans->lang('Comment');
        }

        $details = [];
        foreach ($fields as $field) {
            $type = $this->util->html($field->fullType);
            if ($field->null) {
                $type .= ' <i>nullable</i>'; // ' <i>NULL</i>';
            }
            if ($field->autoIncrement) {
                $type .= ' <i>' . $this->trans->lang('Auto Increment') . '</i>';
            }
            if ($field->default !== '') {
                $type .= /*' ' . $this->trans->lang('Default value') .*/ ' [<b>' . $this->util->html($field->default) . '</b>]';
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
        if (!$this->driver->support('indexes')) {
            return null;
        }

        // From table.inc.php
        $indexes = $this->driver->indexes($table);
        $mainActions = [
            'create' => $this->trans->lang('Alter indexes'),
        ];

        $headers = [
            $this->trans->lang('Name'),
            $this->trans->lang('Type'),
            $this->trans->lang('Column'),
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
                if (\array_key_exists($key, $index->lengths)) {
                    $value .= '(' . $index->lengths[$key] . ')';
                }
                if (\array_key_exists($key, $index->descs)) {
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
        if (!$this->driver->supportForeignKeys($status)) {
            return null;
        }

        // From table.inc.php
        $mainActions = [
            $this->trans->lang('Add foreign key'),
        ];

        $headers = [
            $this->trans->lang('Name'),
            $this->trans->lang('Source'),
            $this->trans->lang('Target'),
            $this->trans->lang('ON DELETE'),
            $this->trans->lang('ON UPDATE'),
        ];

        $foreignKeys = $this->driver->foreignKeys($table);
        if (!$foreignKeys) {
            $foreignKeys = [];
        }
        $details = [];
        // From table.inc.php
        foreach ($foreignKeys as $name => $foreignKey) {
            $target = '';
            if ($foreignKey->database != '') {
                $target .= '<b>' . $this->util->html($foreignKey->database) . '</b>.';
            }
            if ($foreignKey->schema != '') {
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
        if (!$this->driver->support('trigger')) {
            return null;
        }

        $mainActions = [
            $this->trans->lang('Add trigger'),
        ];

        $headers = [
            $this->trans->lang('Name'),
            '&nbsp;',
            '&nbsp;',
            '&nbsp;',
        ];

        $details = [];
        // From table.inc.php
        $triggers = $this->driver->triggers($table);
        foreach ($triggers as $name => $trigger) {
            $details[] = [
                $this->util->html($trigger->timing),
                $this->util->html($trigger->event),
                $this->util->html($name),
                $this->trans->lang('Alter'),
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
        $this->referencableTables = $this->driver->referencableTables($table);
        $this->foreignKeys = [];
        foreach ($this->referencableTables as $tableName => $field) {
            $name = \str_replace('`', '``', $tableName) . '`' . \str_replace('`', '``', $field->name);
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
        if ($type && !$this->driver->typeExists($type) && !isset($this->foreignKeys[$type]) &&
            !\array_key_exists($this->trans->lang('Current'), $extraTypes)) {
            $extraTypes[$this->trans->lang('Current')] = [$type];
        }
        if ($this->foreignKeys) {
            $this->driver->setStructuredType($this->trans->lang('Foreign keys'), $this->foreignKeys);
        }
        return \array_merge($extraTypes, $this->driver->structuredTypes());
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
            'table-save' => $this->trans->lang('Save'),
            'table-cancel' => $this->trans->lang('Cancel'),
        ];

        // From create.inc.php
        $status = [];
        $fields = [];
        if ($table !== '') {
            $status = $this->driver->tableStatus($table);
            if (!$status) {
                throw new Exception($this->trans->lang('No tables.'));
            }
            $fields = $this->driver->fields($table);
        }

        $this->getForeignKeys($table);

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
            $field->unsignedHidden = !(!$type || \preg_match($this->driver->numberRegex(), $type));
            $field->onUpdateHidden = !\preg_match('~timestamp|datetime~', $type);
            $field->onDeleteHidden = !\preg_match('~`~', $type);
        }
        $options = [
            'hasAutoIncrement' => $hasAutoIncrement,
            'onUpdate' => ['CURRENT_TIMESTAMP'],
            'onDelete' => $this->driver->onActions(),
        ];

        $collations = $this->driver->collations();
        $engines = $this->driver->engines();
        $support = [
            'columns' => $this->driver->support('columns'),
            'comment' => $this->driver->support('comment'),
            'partitioning' => $this->driver->support('partitioning'),
            'move_col' => $this->driver->support('move_col'),
            'drop_col' => $this->driver->support('drop_col'),
        ];

        $foreignKeys = $this->foreignKeys;
        $unsigned = $this->driver->unsigned();
        // Give the var a better name
        $table = $status;
        return \compact('mainActions', 'table', 'foreignKeys', 'fields',
            'options', 'collations', 'engines', 'support', 'unsigned');
    }

    /**
     * Get fields for a new column
     *
     * @return array
     */
    public function getTableField()
    {
        $this->getForeignKeys();
        $field = new TableFieldEntity();
        $field->types = $this->getFieldTypes();
        return $field;
    }

    /**
     * Create or alter a table
     *
     * @param array  $values    The table values
     * @param string $table     The table name
     * @param array $origFields The table fields
     *
     * @return array
     */
    private function createOrAlterTable(array $values, string $table = '', array $origFields = [])
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
            $orig = $field['orig'];
            $field = TableFieldEntity::make($field);
            $foreignKey = $this->foreignKeys[$field->type] ?? null;
            //! can collide with user defined type
            $typeField = ($foreignKey === null ? $field :
                TableFieldEntity::make($this->referencableTables[$foreignKey]));
            // Originally, deleted fields have the "field" field set to an empty string.
            // But in our implementation, the "name" field is not set.
            if ($field->name != '') {
                $field->autoIncrement = ($key == $values['autoIncrementCol']);

                $processedField = $this->util->processField($field, $typeField);
                $allFields[] = [$orig, $processedField, $after];
                if (!$origField || $field->changed($origField)) {
                    $fields[] = [$orig, $processedField, $after];
                    if ($orig != '' || $after) {
                        $useAllFields = true;
                    }
                }
                if ($foreignKey !== null) {
                    $fkey = new ForeignKeyEntity();
                    $fkey->table = $this->foreignKeys[$field->type];
                    $fkey->source = [$field->name];
                    $fkey->target = [$typeField['field']];
                    $fkey->onDelete = $field->onDelete;
                    $foreign[$this->driver->escapeId($field->name)] =
                        ($table != '' && $this->driver->jush() != 'sqlite' ? 'ADD' : ' ') .
                        $this->driver->formatForeignKey($fkey);
                }
                $after = ' AFTER ' . $this->driver->escapeId($field->name);
            } elseif ($orig != '') {
                // A missing "name" field and a not empty "orig" field means the column is to be dropped.
                // We also append null in the array because the drivers code accesses field at position 1.
                $useAllFields = true;
                $fields[] = [$orig, null];
            }
            if ($orig != '') {
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
        //             $partitions[] = "\n  PARTITION " . $this->driver->escapeId($val) .
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
        // elseif($this->driver->support('partitioning') &&
        //     \preg_match('~partitioned~', $this->tableStatus->Create_options))
        // {
        //     $partitioning .= "\nREMOVE PARTITIONING";
        // }

        if (!isset($values['comment'])) {
            $values['comment'] = '';
        }
        if (!isset($values['engine']) || !$values['engine']) {
            $values['engine'] = '';
        }
        if (!isset($values['collation']) || !$values['collation']) {
            $values['collation'] = '';
        }

        if ($this->tableStatus != null) {
            // if ($values['comment'] == $this->tableStatus->comment) {
            //     $values['comment'] = null;
            // }
            if ($values['engine'] == $this->tableStatus->engine) {
                $values['engine'] = '';
            }
            if ($values['collation'] == $this->tableStatus->collation) {
                $values['collation'] = '';
            }
        }

        $name = \trim($values['name']);
        $autoIncrement = $this->util->number($this->util->input()->getAutoIncrementStep());
        if ($this->driver->jush() == 'sqlite' && ($useAllFields || $foreign)) {
            $fields = $allFields;
        }

        $success = $this->driver->alterTable($table, $name, $fields, $foreign, $values['comment'],
            $values['engine'], $values['collation'], \intval($autoIncrement), $partitioning);

        $error = $this->driver->error();

        return \compact('success', 'error');
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
        $results = $this->createOrAlterTable($values);
        $results['message'] = $this->trans->lang('Table has been created.');
        return $results;
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
        $origFields = $this->driver->fields($table);
        $this->tableStatus = $this->driver->tableStatus($table);
        if (!$this->tableStatus) {
            throw new Exception($this->trans->lang('No tables.'));
        }

        $results = $this->createOrAlterTable($values, $table, $origFields);
        $results['message'] = $this->trans->lang('Table has been altered.');
        return $results;
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
        $success = $this->driver->dropTables([$table]);

        $error = $this->driver->error();

        $message = $this->trans->lang('Table has been dropped.');

        return \compact('success', 'message', 'error');
    }
}
