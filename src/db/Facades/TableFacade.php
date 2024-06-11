<?php

namespace Lagdo\DbAdmin\Db\Facades;

use Exception;
use Lagdo\DbAdmin\Driver\Entity\TableEntity;
use Lagdo\DbAdmin\Driver\Entity\TableFieldEntity;

use function compact;
use function preg_match;
use function ksort;
use function array_key_exists;
use function array_map;
use function implode;
use function trim;

/**
 * Facade to table functions
 */
class TableFacade extends AbstractFacade
{
    use Traits\TableTrait;

    /**
     * @var string
     */
    private $after = '';

    /**
     * @var array
     */
    private $fields = [];

    /**
     * @var TableEntity
     */
    private $attrs;

    /**
     * Get details about a table
     *
     * @param string $table     The table name
     *
     * @return array
     */
    public function getTableInfo(string $table): array
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

        return compact('mainActions', 'title', 'comment', 'tabs');
    }

    /**
     * Get the fields of a table
     *
     * @param string $table The table name
     *
     * @return array
     * @throws Exception
     */
    public function getTableFields(string $table): array
    {
        // From table.inc.php
        $fields = $this->driver->fields($table);
        if (empty($fields)) {
            throw new Exception($this->driver->error());
        }

        $mainActions = $this->getTableLinks();

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
            $detail = [
                'name' => $this->util->html($field->name),
                'type' => $this->getFieldType($field),
                'collation' => $this->util->html($field->collation),
            ];
            if ($hasComment) {
                $detail['comment'] = $this->util->html($field->comment);
            }
            $details[] = $detail;
        }

        return compact('mainActions', 'headers', 'details');
    }

    /**
     * Get the indexes of a table
     *
     * @param string $table     The table name
     *
     * @return array|null
     */
    public function getTableIndexes(string $table): ?array
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
        foreach ($indexes as $name => $index) {
            ksort($index->columns); // enforce correct columns order
            $print = [];
            foreach ($index->columns as $key => $val) {
                $value = '<i>' . $this->util->html($val) . '</i>';
                if (array_key_exists($key, $index->lengths)) {
                    $value .= '(' . $index->lengths[$key] . ')';
                }
                if (array_key_exists($key, $index->descs)) {
                    $value .= ' DESC';
                }
                $print[] = $value;
            }
            $details[] = [
                'name' => $this->util->html($name),
                'type' => $index->type,
                'desc' => implode(', ', $print),
            ];
        }

        return compact('mainActions', 'headers', 'details');
    }

    /**
     * Get the foreign keys of a table
     *
     * @param string $table     The table name
     *
     * @return array|null
     */
    public function getTableForeignKeys(string $table): ?array
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
                '(' . implode(', ', array_map(function ($key) {
                    return $this->util->html($key);
                }, $foreignKey->target)) . ')';
            $details[] = [
                'name' => $this->util->html($name),
                'source' => '<i>' . implode(
                    '</i>, <i>',
                    array_map(function ($key) {
                        return $this->util->html($key);
                    }, $foreignKey->source)
                ) . '</i>',
                'target' => $target,
                'onDelete' => $this->util->html($foreignKey->onDelete),
                'onUpdate' => $this->util->html($foreignKey->onUpdate),
            ];
        }

        return compact('mainActions', 'headers', 'details');
    }

    /**
     * Get the triggers of a table
     *
     * @param string $table     The table name
     *
     * @return array|null
     */
    public function getTableTriggers(string $table): ?array
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

        return compact('mainActions', 'headers', 'details');
    }

    /**
     * Get required data for create/update on tables
     *
     * @param string $table The table name
     *
     * @return array
     * @throws Exception
     */
    public function getTableData(string $table = ''): array
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
            $hasAutoIncrement = $hasAutoIncrement || $field->autoIncrement;
            $field->hasDefault = $field->default !== null;
            if (preg_match('~^CURRENT_TIMESTAMP~i', $field->onUpdate)) {
                $field->onUpdate = 'CURRENT_TIMESTAMP';
            }

            $type = $field->type;
            $field->types = $this->getFieldTypes($type);
            $field->lengthRequired = !$field->length && preg_match('~var(char|binary)$~', $type);
            $field->collationHidden = !preg_match('~(char|text|enum|set)$~', $type);
            $field->unsignedHidden = !(!$type || preg_match($this->driver->numberRegex(), $type));
            $field->onUpdateHidden = !preg_match('~timestamp|datetime~', $type);
            $field->onDeleteHidden = !preg_match('~`~', $type);
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
        return compact('mainActions', 'table', 'foreignKeys', 'fields',
            'options', 'collations', 'engines', 'support', 'unsigned');
    }

    /**
     * Get fields for a new column
     *
     * @return TableFieldEntity
     */
    public function getTableField(): TableFieldEntity
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
     *
     * @return void
     */
    private function makeTableAttrs(array $values, string $table = '')
    {
        // From create.inc.php
        if ($values['autoIncrementCol']) {
            $values['fields'][$values['autoIncrementCol']]['autoIncrement'] = true;
        }

        $this->attrs = new TableEntity(trim($values['name']));
        $this->after = ' FIRST';

        $this->getForeignKeys();

        $this->fields = ($table !== '' ? $this->driver->fields($table) : []);
        foreach ($values['fields'] as $key => $field) {
            $orig = $field['orig'];
            $field = TableFieldEntity::make($field);
            $field->autoIncrement = ($key == $values['autoIncrementCol']);
            // Originally, deleted fields have the "field" field set to an empty string.
            // But in our implementation, the "name" field is not set.
            $this->addFieldToAttrs($field, $orig, $table);
        }

        // For now, partitioning is not implemented
        // $this->setPartitionAttr();

        $this->setValueAttrs($values);
    }

    /**
     * Create a table
     *
     * @param array  $values    The table values
     *
     * @return array
     */
    public function createTable(array $values): array
    {
        $this->makeTableAttrs($values);
        $success = $this->driver->createTable($this->attrs);
        $error = $this->driver->error();
        $message = $this->trans->lang('Table has been created.');

        return compact('success', 'error', 'message');
    }

    /**
     * Alter a table
     *
     * @param string $table The table name
     * @param array $values The table values
     *
     * @return array
     * @throws Exception
     */
    public function alterTable(string $table, array $values): array
    {
        $this->tableStatus = $this->driver->tableStatus($table);
        if (!$this->tableStatus) {
            throw new Exception($this->trans->lang('No tables.'));
        }

        $this->makeTableAttrs($values, $table);
        $success = $this->driver->alterTable($table, $this->attrs);
        $error = $this->driver->error();
        $message = $this->trans->lang('Table has been altered.');

        return compact('success', 'error', 'message');
    }

    /**
     * Drop a table
     *
     * @param string $table     The table name
     *
     * @return array
     */
    public function dropTable(string $table): array
    {
        $success = $this->driver->dropTables([$table]);
        $error = $this->driver->error();
        $message = $this->trans->lang('Table has been dropped.');

        return compact('success', 'message', 'error');
    }
}
