<?php

namespace Lagdo\DbAdmin\DbAdmin\Traits;

use Lagdo\DbAdmin\Driver\Entity\TableFieldEntity;

use function is_array;
use function in_array;
use function json_encode;
use function reset;
use function count;
use function preg_match;
use function preg_match_all;
use function stripcslashes;
use function str_replace;
use function is_int;
use function explode;
use function substr_count;
use function min;

trait QueryInputTrait
{
    /**
     * @param TableFieldEntity $field
     * @param string $name
     * @param string|null $function
     * @param array $functions
     *
     * @return array
     */
    private function getEntryFunctions(TableFieldEntity $field, string $name, $function, array $functions): array
    {
        // Input for functions
        if ($field->type == "enum") {
            return [
                'type' => 'name',
                'name' => $this->util->html($functions[""] ?? ''),
            ];
        }
        if (count($functions) > 1) {
            $hasFunction = (in_array($function, $functions) || isset($functions[$function]));
            return [
                'type' => 'select',
                'name' => "function[$name]",
                'options' => $functions,
                'selected' => $function === null || $hasFunction ? $function : "",
            ];
        }
        return [
            'type' => 'name',
            'name' => $this->util->html(reset($functions)),
        ];
    }

    /**
     * Get options to display edit field
     *
     * @param TableFieldEntity $field
     * @param bool $select
     * @param mixed $value
     *
     * @return array
     */
    private function getEnumValues(TableFieldEntity $field, bool $select, $value): array
    {
        $values = [];
        if (($select)) {
            $values[] = ['value' => '-1', 'checked' => true,
                'text' => '<i>' . $this->trans->lang('original') . '</i>'];
        }
        if ($field->null) {
            $values[] =  ['value' => '', 'checked' => $value === null && !$select, 'text' => '<i>NULL</i>'];
        }

        // From functions.inc.php (function enum_input())
        $empty = 0; // 0 - empty
        $values[] = ['value' => $empty, 'checked' => is_array($value) ? in_array($empty, $value) : $value === 0,
            'text' => '<i>' . $this->trans->lang('empty') . '</i>'];

        preg_match_all("~'((?:[^']|'')*)'~", $field->fullType, $matches);
        foreach ($matches[1] as $i => $val) {
            $val = stripcslashes(str_replace("''", "'", $val));
            $checked = (is_int($value) ? $value == $i + 1 :
                (is_array($value) ? in_array($i+1, $value) : $value === $val));
            $values[] = ['value' => $i + 1, 'checked' => $checked, 'text' => $this->util->html($val)];
        }

        return $values;
    }

    /**
     * @param TableFieldEntity $field
     * @param array $attrs
     * @param mixed $value
     *
     * @return array
     */
    private function getSetInput(TableFieldEntity $field, array $attrs, $value): array
    {
        $values = [];
        preg_match_all("~'((?:[^']|'')*)'~", $field->length, $matches);
        foreach ($matches[1] as $i => $val) {
            $val = stripcslashes(str_replace("''", "'", $val));
            $checked = (is_int($value) ? ($value >> $i) & 1 : in_array($val, explode(",", $value), true));
            $values[] = ['value=' => (1 << $i), 'checked' => $checked, 'text' => $this->util->html($val)];
        }
        return ['type' => 'checkbox', 'attrs' => $attrs, 'values' => $values];
    }

    /**
     * @param TableFieldEntity $field
     * @param array $attrs
     * @param mixed $value
     *
     * @return array
     */
    private function getBlobInput(TableFieldEntity $field, array $attrs, $value): array
    {
        if (preg_match('~text|lob|memo~i', $field->type) && $this->driver->jush() != "sqlite") {
            $attrs['cols'] = 50;
            $attrs['rows'] = 12;
        } else {
            $rows = min(12, substr_count($value, "\n") + 1);
            $attrs['cols'] = 30;
            $attrs['rows'] = $rows;
            if ($rows == 1) {
                $attrs['style'] = 'height: 1.2em;';
            }
        }
        return ['type' => 'textarea', 'attrs' => $attrs, 'value' => $this->util->html($value)];
    }

    private function getMaxLength(TableFieldEntity $field)
    {
        $unsigned = $field->unsigned;
        // int(3) is only a display hint
        if (!preg_match('~int~', $field->type) &&
            preg_match('~^(\d+)(,(\d+))?$~', $field->length, $match)) {
            $length1 = preg_match("~binary~", $field->type) ? 2 : 1;
            $length2 = ($match[3] ?? false) ? 1 : 0;
            $length3 = ($match[2] ?? false) && !$unsigned ? 1 : 0;
            return $length1 * $match[1] + $length2 + $length3;
        }
        if ($this->driver->typeExists($field->type)) {
            return $this->driver->type($field->type) + ($unsigned ? 0 : 1);
        }
        return 0;
    }

    /**
     * @param TableFieldEntity $field
     * @param array $attrs
     * @param mixed $value
     * @param string|null $function
     * @param array $functions
     *
     * @return array
     */
    private function getDefaultInput(TableFieldEntity $field, array $attrs, $value, $function, array $functions): array
    {
        $maxlength = $this->getMaxLength($field);
        if ($this->driver->jush() == 'sql' && $this->driver->minVersion(5.6) && preg_match('~time~', $field->type)) {
            $maxlength += 7; // microtime
        }
        if ($maxlength > 0) {
            $attrs['data-maxlength'] = $maxlength;
        }
        // type='date' and type='time' display localized value which may be confusing,
        // type='datetime' uses 'T' as date and time separator
        $hasFunction = (in_array($function, $functions) || isset($functions[$function]));
        if ((!$hasFunction || $function === "") &&
            preg_match('~(?<!o)int(?!er)~', $field->type) &&
            !preg_match('~\[\]~', $field->fullType)) {
            $attrs['type'] = 'number';
        }
        if (preg_match('~char|binary~', $field->type) && $maxlength > 20) {
            $attrs['size'] = 40;
        }
        $attrs['value'] = $this->util->html($value);
        return ['type' => 'input', 'attrs' => $attrs];
    }

    /**
     * @param TableFieldEntity $field
     * @param string $name
     * @param mixed $value
     * @param string|null $function
     * @param array $functions
     * @param array $options
     *
     * @return array
     */
    private function getEntryInput(TableFieldEntity $field, string $name, $value, $function, array $functions, array $options): array
    {
        $attrs = ['name' => "fields[$name]"];
        if ($field->type == "enum") {
            return ['type' => 'radio', 'attrs' => $attrs,
                'values' => $this->getEnumValues($field, isset($options["select"]), $value)];
        }
        if (preg_match('~bool~', $field->type)) {
            $attrs['checked'] = preg_match('~^(1|t|true|y|yes|on)$~i', $value);
            return ['type' => 'bool', 'attrs' => $attrs];
        }
        if ($field->type == "set") {
            return $this->getSetInput($field, $attrs, $value);
        }
        if (preg_match('~blob|bytea|raw|file~', $field->type) && $this->util->iniBool("file_uploads")) {
            $attrs['name'] = "fields-$name";
            return ['type' => 'file', 'attrs' => $attrs];
        }
        if (preg_match('~text|lob|memo~i', $field->type) || preg_match("~\n~", $value)) {
            return $this->getBlobInput($field, $attrs, $value);
        }
        if ($function == "json" || preg_match('~^jsonb?$~', $field->type)) {
            $attrs['cols'] = 50;
            $attrs['rows'] = 12;
            return ['type' => 'textarea', 'attrs' => $attrs, 'value' => $this->util->html($value)];
        }
        return $this->getDefaultInput($field, $attrs, $value, $function, $functions);
    }

    /**
     * Get data for an input field
     *
     * @param TableFieldEntity $field
     * @param mixed $value
     * @param string|null $function
     * @param array $options
     *
     * @return array
     */
    protected function getFieldInput(TableFieldEntity $field, $value, $function, array $options): array
    {
        // From functions.inc.php (function input($field, $value, $function))
        $name = $this->util->html($this->util->bracketEscape($field->name));
        $save = $options["save"];
        $reset = ($this->driver->jush() == "mssql" && $field->autoIncrement);
        if (is_array($value) && !$function) {
            $value = json_encode($value, JSON_PRETTY_PRINT);
            $function = "json";
        }
        if ($reset && !$save) {
            $function = null;
        }
        $functions = [];
        if ($reset) {
            $functions["orig"] = $this->trans->lang('original');
        }
        $functions += $this->util->editFunctions($field);
        return [
            'type' => $this->util->html($field->fullType),
            'name' => $name,
            'field' => [
                'type' => $field->type,
            ],
            'functions' => $this->getEntryFunctions($field, $name, $function, $functions),
            'input' => $this->getEntryInput($field, $name, $value, $function, $functions, $options),
        ];
    }
}
