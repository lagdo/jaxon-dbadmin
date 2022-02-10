<?php

namespace Lagdo\DbAdmin\DbAdmin\Traits;

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
            !array_key_exists($this->trans->lang('Current'), $extraTypes)) {
            $extraTypes[$this->trans->lang('Current')] = [$type];
        }
        if (!empty($this->foreignKeys)) {
            $this->driver->setStructuredType($this->trans->lang('Foreign keys'), $this->foreignKeys);
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
     * Print links after select heading
     * Copied from selectLinks() in adminer.inc.php
     *
     * @param string $set New item options, NULL for no new item
     *
     * @return array
     */
    protected function getTableLinks($set = null): array
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
     * @param TableFieldEntity $field
     *
     * @return string
     */
    private function getFieldType(TableFieldEntity $field): string
    {
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
        return $type;
    }

    /**
     * @param TableFieldEntity $field
     * @param string $table
     *
     * @return void
     */
    private function addFieldToAttrs(TableFieldEntity $field, string $table)
    {
        $foreignKey = $this->foreignKeys[$field->type] ?? null;
        //! Can collide with user defined type
        $typeField = ($foreignKey === null ? $field :
            TableFieldEntity::make($this->referencableTables[$foreignKey]));
        $processedField = $this->util->processField($field, $typeField);
        $origField = $this->fields[$field->name] ?? null;
        if ($field->name === '') {
            $this->attrs->fields[] = ['', $processedField, $this->after];
        } elseif ($origField !== null && $field->changed($origField)) {
            $this->attrs->edited[] = [$field->name, $processedField, $this->after];
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
    //     if($partition_by[$values['partition_by']])
    //     {
    //         $partitions = [];
    //         if($values['partition_by'] == 'RANGE' || $values['partition_by'] == 'LIST')
    //         {
    //             foreach(\array_filter($values['partition_names']) as $key => $val)
    //             {
    //                 $value = $values['partition_values'][$key];
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
    //     }
    //     elseif($this->driver->support('partitioning') &&
    //         \preg_match('~partitioned~', $this->tableStatus->Create_options))
    //     {
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
        $this->attrs->autoIncrement = intval($this->util->number($this->util->input()->getAutoIncrementStep()));
    }
}
