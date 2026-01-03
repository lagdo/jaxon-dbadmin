<?php

namespace Lagdo\DbAdmin\Db\Driver\Facades;

use Lagdo\DbAdmin\Db\Page\Ddl\TableContent;
use Lagdo\DbAdmin\Db\Page\Ddl\TableHeader;
use Lagdo\DbAdmin\Driver\Entity\ForeignKeyEntity;
use Lagdo\DbAdmin\Driver\Entity\TableEntity;
use Lagdo\DbAdmin\Driver\Entity\TableFieldEntity;
use Exception;

use function array_map;
use function compact;
use function count;
use function intval;
use function in_array;
use function str_replace;
use function trim;

/**
 * Facade to table functions
 */
class TableFacade extends AbstractFacade
{
    /**
     * The current table status
     *
     * @var mixed
     */
    protected $tableStatus = null;

    /**
     * @var array
     */
    protected $referencableTables = [];

    /**
     * @var array<string,string>
     */
    protected $foreignKeys = [];

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
     * @var TableHeader|null
     */
    private TableHeader|null $tableHeader = null;

    /**
     * @var TableContent|null
     */
    private TableContent|null $tableContent = null;

    /**
     * @return TableHeader
     */
    private function header(): TableHeader
    {
        return $this->tableHeader ??= new TableHeader($this->page, $this->driver, $this->utils);
    }

    /**
     * @return TableContent
     */
    private function content(): TableContent
    {
        return $this->tableContent ??= new TableContent($this->page, $this->driver, $this->utils);
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
            $name = str_replace('`', '``', $tableName) . '`' .
                str_replace('`', '``', $field->name);
            // not escapeId() - used in JS
            $this->foreignKeys[$name] = $tableName;
        }
    }

    /**
     * Get field types
     *
     * @param string $type  The type name
     * @param array $extraTypes
     *
     * @return array
     */
    public function getFieldTypes(string $type = '', array $extraTypes = []): array
    {
        // From includes/editing.inc.php
        if ($type !== '' && !$this->driver->typeExists($type) &&
            !isset($this->foreignKeys[$type]) && !in_array($type, $extraTypes)) {
            $extraTypes[] = $type;
        }

        $structuredTypes = $this->driver->structuredTypes();
        if (!empty($this->foreignKeys)) {
            $structuredTypes[$this->utils->trans->lang('Foreign keys')] = $this->foreignKeys;
        }

        // Change from Adminer:
        // The $extraTypes are all kept in the first entry in the table.
        return count($extraTypes) > 0 ? [$extraTypes, ...$structuredTypes] : $structuredTypes;
    }

    /**
     * Get the current table status
     *
     * @param string $table
     *
     * @return mixed
     */
    protected function status(string $table)
    {
        return $this->tableStatus ??= $this->driver->tableStatusOrName($table, true);
    }

    /**
     * @param TableFieldEntity $field
     * @param string $orig
     * @param string $table
     *
     * @return void
     */
    private function addFieldToAttrs(TableFieldEntity $field, string $orig, string $table)
    {
        if ($field->name === '' && $orig !== '') {
            // A missing "name" field and a not empty "orig" field means the column is to be dropped.
            $this->attrs->dropped[] = $orig;
            return;
        }
        $foreignKey = $this->foreignKeys[$field->type] ?? null;
        //! Can collide with user defined type
        $typeField = ($foreignKey === null ? $field :
            TableFieldEntity::make($this->referencableTables[$foreignKey]));
        $processedField = $this->driver->processField($field, $typeField);
        $origField = $this->fields[$field->name] ?? null;
        $this->after = '';
        if ($orig === '') {
            $this->attrs->fields[] = ['', $processedField, $this->after];
            $this->after = ' AFTER ' . $this->driver->escapeId($field->name);
        } elseif ($origField !== null && !$field->equals($origField)) {
            $this->attrs->edited[] = [$orig, $processedField, $this->after];
        }
        if ($foreignKey !== null) {
            $fkey = new ForeignKeyEntity();
            $fkey->table = $this->foreignKeys[$field->type];
            $fkey->source = [$field->name];
            $fkey->target = [$typeField->name];
            $fkey->onDelete = $field->onDelete;
            $this->attrs->foreign[$this->driver->escapeId($field->name)] =
                ($table != '' && $this->driver->jush() != 'sqlite' ? 'ADD' : ' ') .
                $this->driver->formatForeignKey($fkey);
        }
    }

    /**
     * @return void
     */
    // private function setPartitionAttr()
    // {
    //     $this->attrs->partitioning = '';
    //     if($partition_by[$values['partition_by']]) {
    //         $partitions = [];
    //         if($values['partition_by'] == 'RANGE' || $values['partition_by'] == 'LIST')
    //         {
    //             foreach(\array_filter($values['partition_names']) as $key => $val)
    //             {
    //                 $value = $values['partition_values'][$key];
    //                 // Todo: use match
    //                 $partitions[] = "\n  PARTITION " . $this->driver->escapeId($val) .
    //                     ' VALUES ' . ($values['partition_by'] == 'RANGE' ? 'LESS THAN' : 'IN') .
    //                     ($value != '' ? ' ($value)' : ' MAXVALUE'); //! SQL injection
    //             }
    //         }
    //         $this->attrs->partitioning .= "\nPARTITION BY $values[partition_by]($values[partition])" .
    //             ($partitions // $values['partition'] can be expression, not only column
    //             ? ' (' . \implode(',', $partitions) . "\n)"
    //             : ($values['partitions'] ? ' PARTITIONS ' . (+$values['partitions']) : '')
    //         );
    //     } elseif($this->driver->support('partitioning') &&
    //         \preg_match('~partitioned~', $this->tableStatus->Create_options)) {
    //         $this->attrs->partitioning .= "\nREMOVE PARTITIONING";
    //     }
    // }

    /**
     * @param array $values
     *
     * @return void
     */
    private function setValueAttrs(array $values)
    {
        foreach (['comment', 'engine', 'collation'] as $attr) {
            $this->attrs->$attr = !empty($values[$attr]) ? $values[$attr] : '';
            if ($this->tableStatus != null) {
                // No change.
                if ($this->attrs->$attr == $this->tableStatus->$attr) {
                    $this->attrs->$attr = '';
                }
            }
        }
        $this->attrs->autoIncrement = intval($this->utils->str->number($this->utils->input->getAutoIncrementStep()));
    }

    /**
     * Get details about a table
     *
     * @param string $table     The table name
     *
     * @return array
     */
    public function getTableInfo(string $table): array
    {
        // From table.inc.php
        $status = $this->status($table);

        return $this->header()->infos($table, $status);
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

        $status = $this->status($table);

        return [
            'headers' => $this->header()->fields(),
            'details' => $this->content()->fields($fields, $status?->collation ?? ''),
        ];
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

        return [
            'headers' => $this->header()->indexes(),
            'details' => $this->content()->indexes($indexes),
        ];
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

        $foreignKeys = $this->driver->foreignKeys($table);

        return [
            'headers' => $this->header()->foreignKeys(),
            'details' => $this->content()->foreignKeys($foreignKeys),
        ];
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

        // From table.inc.php
        $triggers = $this->driver->triggers($table);

        return [
            'headers' => $this->header()->triggers(),
            'details' => $this->content()->triggers($triggers),
        ];
    }

    /**
     * Get required data for create/update on tables
     *
     * @param string $table The table name
     *
     * @return array
     * @throws Exception
     */
    public function getTableMetadata(string $table = ''): array
    {
        // From create.inc.php
        $status = null;
        $fields = [];
        if ($table !== '') {
            $status = $this->driver->tableStatus($table);
            if (!$status) {
                throw new Exception($this->utils->trans->lang('No tables.'));
            }
            $fields = $this->driver->fields($table);
        }

        $this->getForeignKeys($table);

        $fields = array_map(function($field) {
            $field->types = $this->getFieldTypes($field->type);
            return $field;
        }, $fields);

        return $this->content()->metadata($status, $fields, $this->foreignKeys);
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
        $message = $this->utils->trans->lang('Table has been created.');

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
            throw new Exception($this->utils->trans->lang('No tables.'));
        }

        $this->makeTableAttrs($values, $table);
        $success = $this->driver->alterTable($table, $this->attrs);
        $error = $this->driver->error();
        $message = $this->utils->trans->lang('Table has been altered.');

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
        $message = $this->utils->trans->lang('Table has been dropped.');

        return compact('success', 'message', 'error');
    }
}
