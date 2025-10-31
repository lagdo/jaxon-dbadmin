<?php

namespace Lagdo\DbAdmin\Db\Facades\Traits;

use Lagdo\DbAdmin\Driver\Entity\ForeignKeyEntity;
use Lagdo\DbAdmin\Driver\Entity\TableFieldEntity;

use function array_key_exists;
use function array_merge;
use function str_replace;
use function intval;

trait TableTrait
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
     * @var array
     */
    protected $foreignKeys = [];

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
     *
     * @return array
     */
    public function getFieldTypes(string $type = ''): array
    {
        // From includes/editing.inc.php
        $extraTypes = [];
        if ($type && !$this->driver->typeExists($type) && !isset($this->foreignKeys[$type]) &&
            !array_key_exists($this->utils->trans->lang('Current'), $extraTypes)) {
            $extraTypes[$this->utils->trans->lang('Current')] = [$type];
        }
        if (!empty($this->foreignKeys)) {
            $this->driver->setStructuredType($this->utils->trans->lang('Foreign keys'), $this->foreignKeys);
        }
        return array_merge($extraTypes, $this->driver->structuredTypes());
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
        if (!$this->tableStatus) {
            $this->tableStatus = $this->driver->tableStatusOrName($table, true);
        }
        return $this->tableStatus;
    }

    /**
     * @param TableFieldEntity $field
     *
     * @return string
     */
    private function getFieldType(TableFieldEntity $field): string
    {
        $type = $this->utils->str->html($field->fullType);
        if ($field->null) {
            $type .= ' <i>nullable</i>'; // ' <i>NULL</i>';
        }
        if ($field->autoIncrement) {
            $type .= ' <i>' . $this->utils->trans->lang('Auto Increment') . '</i>';
        }
        if ($field->default !== '') {
            $type .= /*' ' . $this->utils->trans->lang('Default value') .*/ ' [<b>' . $this->utils->str->html($field->default) . '</b>]';
        }
        return $type;
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
}
