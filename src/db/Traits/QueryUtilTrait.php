<?php

namespace Lagdo\DbAdmin\Db\Traits;

use Lagdo\DbAdmin\Driver\Entity\TableFieldEntity;

trait QueryUtilTrait
{
    /**
     * Filter length value including enums
     *
     * @param string $length
     *
     * @return string
     */
    abstract public function processLength(string $length): string;

    /**
     * @param TableFieldEntity $field
     * @param array $values First entries
     * @param bool $update
     *
     * @return string[]
     */
    private function getEditFunctionNames(TableFieldEntity $field, array $values, bool $update): array
    {
        $names = $values;
        foreach ($this->driver->editFunctions() as $key => $functions) {
            if (!$key || (!isset($this->input->values['call']) && $update)) { // relative functions
                foreach ($functions as $pattern => $value) {
                    if (!$pattern || preg_match("~$pattern~", $field->type)) {
                        $names[] = $value;
                    }
                }
            }
            if ($key && !preg_match('~set|blob|bytea|raw|file|bool~', $field->type)) {
                $names[] = 'SQL';
            }
        }
        return $names;
    }

    /**
     * Functions displayed in edit form
     *
     * @param TableFieldEntity $field Single field from fields()
     *
     * @return array
     */
    public function editFunctions(TableFieldEntity $field): array
    {
        $update = isset($this->input->values['select']); // || $this->where([]);
        if ($field->autoIncrement && !$update) {
            return [$this->trans->lang('Auto Increment')];
        }

        $names = ($field->null ? ['NULL', ''] : ['']);
        return $this->getEditFunctionNames($field, $names, $update);
    }

    /**
     * Create SQL string from field type
     *
     * @param TableFieldEntity $field
     * @param string $collate
     *
     * @return string
     */
    private function processType(TableFieldEntity $field, string $collate = 'COLLATE'): string
    {
        $collation = '';
        if (preg_match('~char|text|enum|set~', $field->type) && $field->collation) {
            $collation = " $collate " . $this->driver->quote($field->collation);
        }
        $sign = '';
        if (preg_match($this->driver->numberRegex(), $field->type) &&
            in_array($field->unsigned, $this->driver->unsigned())) {
            $sign = ' ' . $field->unsigned;
        }
        return ' ' . $field->type . $this->processLength($field->length) . $sign . $collation;
    }

    /**
     * Create SQL string from field
     *
     * @param TableFieldEntity $field Basic field information
     * @param TableFieldEntity $typeField Information about field type
     *
     * @return array
     */
    public function processField(TableFieldEntity $field, TableFieldEntity $typeField): array
    {
        $onUpdate = '';
        if (preg_match('~timestamp|datetime~', $field->type) && $field->onUpdate) {
            $onUpdate = ' ON UPDATE ' . $field->onUpdate;
        }
        $comment = '';
        if ($this->driver->support('comment') && $field->comment !== '') {
            $comment = ' COMMENT ' . $this->driver->quote($field->comment);
        }
        $null = $field->null ? ' NULL' : ' NOT NULL'; // NULL for timestamp
        $autoIncrement = $field->autoIncrement ? $this->driver->autoIncrement() : null;
        return [$this->driver->escapeId(trim($field->name)), $this->processType($typeField),
            $null, $this->driver->defaultValue($field), $onUpdate, $comment, $autoIncrement];
    }
}
