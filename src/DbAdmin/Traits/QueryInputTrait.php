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
    private function getEntryFunction(TableFieldEntity $field, string $name, $function, array $functions): array
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
            $values[] = [$this->util->html($val), $checked];
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
        return ['type' => 'blob', 'attrs' => $attrs, 'value' => $this->util->html($value)];
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
        $unsigned = $field->unsigned ?? false;
        // int(3) is only a display hint
        $maxlength = (!preg_match('~int~', $field->type) &&
        preg_match('~^(\d+)(,(\d+))?$~', $field->length, $match) ?
            ((preg_match("~binary~", $field->type) ? 2 : 1) * $match[1] + (($match[3] ?? null) ? 1 : 0) +
                (($match[2] ?? false) && !$unsigned ? 1 : 0)) :
            ($this->driver->typeExists($field->type) ? $this->driver->type($field->type) + ($unsigned ? 0 : 1) : 0));
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
        return ['type' => 'input', 'attrs' => $attrs, 'value' => $this->util->html($value)];
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
            return ['type' => 'radio', 'attrs' => $attrs, 'values' => [isset($options["select"]), $field, $attrs, $value]];
        }
        if (preg_match('~bool~', $field->type)) {
            return ['type' => 'checkbox', 'attrs' => $attrs, 'values' => [preg_match('~^(1|t|true|y|yes|on)$~i', $value)]];
        }
        if ($field->type == "set") {
            return $this->getSetInput($field, $attrs, $value);
        }
        if (preg_match('~blob|bytea|raw|file~', $field->type) && $this->util->iniBool("file_uploads")) {
            return ['type' => 'upload', 'attrs' => $attrs, 'value' => $name];
        }
        if (preg_match('~text|lob|memo~i', $field->type) || preg_match("~\n~", $value)) {
            return $this->getBlobInput($field, $attrs, $value);
        }
        if ($function == "json" || preg_match('~^jsonb?$~', $field->type)) {
            $attrs['cols'] = 50;
            $attrs['rows'] = 12;
            return ['type' => 'json', 'attrs' => $attrs, 'value' => $this->util->html($value)];
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
            'function' => $this->getEntryFunction($field, $name, $function, $functions),
            'input' => $this->getEntryInput($field, $name, $value, $function, $functions, $options),
        ];

        // Input for value
        // The HTML code generated by Adminer is kept here.
        /*$attrs = " name='fields[$name]'";
        $entry['input'] = ['type' => ''];
        if ($field->type == "enum") {
            $entry['input']['type'] = 'radio';
            $entry['input']['value'] = $this->util->editInput(isset($options["select"]), $field, $attrs, $value);
        } elseif (preg_match('~bool~', $field->type)) {
            $entry['input']['type'] = 'checkbox';
            $entry['input']['value'] = ["<input type='hidden'$attrs value='0'>" . "<input type='checkbox'" .
                (preg_match('~^(1|t|true|y|yes|on)$~i', $value) ? " checked='checked'" : "") . "$attrs value='1'>"];
        } elseif ($field->type == "set") {
            $entry['input']['type'] = 'checkbox';
            $entry['input']['value'] = [];
            preg_match_all("~'((?:[^']|'')*)'~", $field->length, $matches);
            foreach ($matches[1] as $i => $val) {
                $val = \stripcslashes(\str_replace("''", "'", $val));
                $checked = (is_int($value) ? ($value >> $i) & 1 : in_array($val, explode(",", $value), true));
                $entry['input']['value'][] = "<label><input type='checkbox' name='fields[$name][$i]' value='" . (1 << $i) . "'" .
                    ($checked ? ' checked' : '') . ">" . $this->util->html($val) . '</label>';
            }
        } elseif (preg_match('~blob|bytea|raw|file~', $field->type) && $this->util->iniBool("file_uploads")) {
            $entry['input']['value'] = "<input type='file' name='fields-$name'>";
        } elseif (($text = preg_match('~text|lob|memo~i', $field->type)) || preg_match("~\n~", $value)) {
            if ($text && $this->driver->jush() != "sqlite") {
                $attrs .= " cols='50' rows='12'";
            } else {
                $rows = min(12, substr_count($value, "\n") + 1);
                $attrs .= " cols='30' rows='$rows'" . ($rows == 1 ? " style='height: 1.2em;'" : ""); // 1.2em - line-height
            }
            $entry['input']['value'] = "<textarea$attrs>" . $this->util->html($value) . '</textarea>';
        } elseif ($function == "json" || preg_match('~^jsonb?$~', $field->type)) {
            $entry['input']['value'] = "<textarea$attrs cols='50' rows='12' class='jush-js'>" .
                $this->util->html($value) . '</textarea>';
        } else {
            $unsigned = $field->unsigned ?? false;
            // int(3) is only a display hint
            $maxlength = (!preg_match('~int~', $field->type) &&
                preg_match('~^(\d+)(,(\d+))?$~', $field->length, $match) ?
                ((preg_match("~binary~", $field->type) ? 2 : 1) * $match[1] + (($match[3] ?? null) ? 1 : 0) +
                (($match[2] ?? false) && !$unsigned ? 1 : 0)) :
                ($this->driver->typeExists($field->type) ? $this->driver->type($field->type) + ($unsigned ? 0 : 1) : 0));
            if ($this->driver->jush() == 'sql' && $this->driver->minVersion(5.6) && preg_match('~time~', $field->type)) {
                $maxlength += 7; // microtime
            }
            // type='date' and type='time' display localized value which may be confusing,
            // type='datetime' uses 'T' as date and time separator
            $hasFunction = (in_array($function, $functions) || isset($functions[$function]));
            $entry['input']['value'] = "<input" . ((!$hasFunction || $function === "") &&
                preg_match('~(?<!o)int(?!er)~', $field->type) &&
                !preg_match('~\[\]~', $field->fullType) ? " type='number'" : "") . " value='" .
                $this->util->html($value) . "'" . ($maxlength ? " data-maxlength='$maxlength'" : "") .
                (preg_match('~char|binary~', $field->type) && $maxlength > 20 ? " size='40'" : "") . "$attrs>";
        }

        return $entry;*/
    }
}
