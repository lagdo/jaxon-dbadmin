<?php

namespace Lagdo\DbAdmin\Admin\Traits;

use Lagdo\DbAdmin\Driver\Entity\TableFieldEntity;

use function preg_match;

trait QueryTrait
{
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
        $dbFunctions = [$this->driver->insertFunctions(), $this->driver->editFunctions()];
        foreach ($dbFunctions as $key => $functions) {
            if (!$key || (!isset($this->utils->input->values['call']) && $update)) { // relative functions
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
        $update = isset($this->utils->input->values['select']); // || $this->where([]);
        if ($field->autoIncrement && !$update) {
            return [$this->utils->trans->lang('Auto Increment')];
        }

        $names = $field->null ? ['NULL', ''] : [''];
        return $this->getEditFunctionNames($field, $names, $update);
    }
}
