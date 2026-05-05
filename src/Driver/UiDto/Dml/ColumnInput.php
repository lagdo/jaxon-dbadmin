<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto\Dml;

use Lagdo\DbAdmin\Support\Driver\AbstractDriverProxy;

use function count;
use function explode;
use function in_array;
use function is_array;
use function is_string;
use function min;
use function preg_match;
use function preg_match_all;
use function stripcslashes;
use function str_replace;
use function substr_count;

/**
 * Make data for HTML elements in the user forms for data row insert and update.
 */
class ColumnInput extends AbstractDriverProxy
{
    /**
     * @var string
     */
    private string $action;

    /**
     * @var string
     */
    private string $operation;

    /**
     * @param string $action
     * @param string $operation
     *
     * @return self
     */
    public function init(string $action, string $operation): self
    {
        $this->action = $action;
        $this->operation = $operation;
        return $this;
    }

    /**
     * @param ColumnDmDto $input
     * @param string $columnValue
     * @param string|null $enumValue
     *
     * @return bool
     */
    private function isChecked(ColumnDmDto $input, string $columnValue, string|null $enumValue): bool
    {
        return !is_array($input->value) ? $input->value === $enumValue :
            in_array($columnValue, $input->value);
    }

    /**
     * Get data for enum or set input field
     * 
     * @param ColumnDmDto $input
     */
    private function getItemList(ColumnDmDto $input, array $attrs, string $default = ""): array|null
    {
        if ($input->type !== 'enum' && $input->type !== 'set' ) {
            // Only for enums and sets
            return null;
        }

        // From html.inc.php: function enum_input(string $type, string $attrs, array $field, $value, string $empty = "")
        $prefix = $input->type === 'enum' ? 'val-' : '';
        $items = [];
        if ($input->column->nullable && $prefix) {
            $items[] = [
                'attrs' => [
                    ...$attrs,
                    'id' => "{$attrs['id']}_null", // Overwrite the id value in the $attrs array.
                ],
                'label' => "<i>$default</i>",
                'value' => 'null',
                'checked' => $this->isChecked($input, 'null', null),
            ];
        }

        preg_match_all("~'((?:[^']|'')*)'~", $input->column->length, $matches);
        foreach (($matches[1] ?? []) as $enumValue) {
            $enumValue = stripcslashes(str_replace("''", "'", $enumValue));
            $columnValue = "$prefix$enumValue";
            $items[] = [
                'attrs' => [
                    ...$attrs,
                    'id' => "{$attrs['id']}_{$enumValue}", // Overwrite the id value in the $attrs array.
                ],
                'label' => $this->utils()->html($enumValue),
                'value' => $this->utils()->html($columnValue),
                'checked' => $this->isChecked($input, $columnValue, $enumValue),
            ];
        }

        return $items;
    }

    /**
     * @param ColumnDmDto $input
     * @param array $attrs
     *
     * @return array
     */
    private function getEnumColumnInput(ColumnDmDto $input, array $attrs): array
    {
        // From adminer.inc.php: function editInput(?string $table, array $field, string $attrs, $value): string
        $items = $this->getItemList($input, $attrs, 'NULL');
        if ($this->action === 'select') {
            // Prepend the value to the item list
            $items = [[
                'attrs' => $attrs,
                'label' => '<i>' . $this->utils()->lang('original') . '</i>',
                'value' => 'orig',
                'checked' => true,
            ], ...$items];
        }

        return [
            'field' => 'enum',
            'items' => $items,
        ];
    }

    /**
     * @param ColumnDmDto $input
     * @param array $attrs
     *
     * @return array
     */
    private function getSetColumnInput(ColumnDmDto $input, array $attrs): array
    {
        if (is_string($input->value)) {
            $input->value = explode(",", $input->value);
        }

        return [
            'field' => 'set',
            'items' => $this->getItemList($input, $attrs),
        ];
    }

    /**
     * @param ColumnDmDto $input
     * @param array $attrs
     *
     * @return array
     */
    private function getBoolColumnInput(ColumnDmDto $input, array $attrs): array
    {
        return [
            'field' => 'bool',
            'attrs' => [
                'hidden' => [
                    ...$attrs,
                    'id' => '', // Unset the id value in the $attrs array
                    'value' => '0',
                ],
                'checkbox' => [
                    ...$attrs,
                    'value' => '1',
                ],
            ],
            'checked' => $input->isChecked(),
        ];
    }

    /**
     * @param ColumnDmDto $input
     *
     * @return bool
     */
    private function isBlob(ColumnDmDto $input): bool
    {
        return $this->utils()->isBlob($input->column) && $this->utils()->iniBool("file_uploads");
    }

    /**
     * @param ColumnDmDto $input
     * @param array $attrs
     *
     * @return array
     */
    private function getFileColumnInput(ColumnDmDto $input, array $attrs): array
    {
        return [
            'field' => 'file',
            'attrs' => [
                'id' => $attrs['id'],
                'name' => "fields-{$input->name}",
            ],
        ];
    }

    /**
     * @param ColumnDmDto $input
     * @param array $attrs
     *
     * @return array
     */
    private function getJsonColumnInput(ColumnDmDto $input, array $attrs): array
    {
        return [
            'field' => 'json',
            'attrs' => [
                ...$attrs,
                'cols' => '50',
                'rows' => '5',
                'class' => 'jush-js',
            ],
            'value' => $this->utils()->html($input->value ?? ''),
        ];
    }

    /**
     * @param ColumnDmDto $input
     *
     * @return bool
     */
    private function textSizeIsFixed(ColumnDmDto $input): bool
    {
        return ($input->isText() && !$this->engine()->sqlite()) || $input->isSearch();
    }

    /**
     * @param ColumnDmDto $input
     * @param array $attrs
     *
     * @return array
     */
    private function getTextColumnInput(ColumnDmDto $input, array $attrs): array
    {
        $columnAttrs = $this->textSizeIsFixed($input) ? [
            'cols' => '50',
            'rows' => '5',
        ] : [
            'cols' => '30',
            'rows' => min(5, substr_count($input->value ?? '', "\n") + 1),
        ];
        return [
            'field' => 'text',
            'attrs' => [
                ...$attrs,
                ...$columnAttrs,
            ],
            'value' => $this->utils()->html($input->value ?? ''),
        ];
    }

    /**
     * @param ColumnDmDto $input
     *
     * @return int
     */
    private function getInputFieldMaxLength(ColumnDmDto $input): int
    {
        $unsigned = $input->column->unsigned;
        $length = $input->column->length;
        $type = $input->type;
        $types = $this->engine()->types();

        $maxlength = (!preg_match('~int~', $type) &&
            preg_match('~^(\d+)(,(\d+))?$~', $length, $match) ?
            ((preg_match("~binary~", $type) ? 2 : 1) *
                ($match[1] ?? 0) + (($match[3] ?? false) ? 1 : 0) +
                (($match[2] ?? false) && !$unsigned ? 1 : 0)) :
            (isset($types[$type]) ? $types[$type] + ($unsigned ? 0 : 1) : 0)
        );

        return $this->engine()->sql() &&
            $this->engine()->minVersion(5.6) &&
            preg_match('~time~', $type) ?
                $maxlength += 7 : // microtime
                $maxlength;
    }

    /**
     * @param ColumnDmDto $input
     * @param array $attrs
     *
     * @return array
     */
    private function getDefaultColumnInput(ColumnDmDto $input, array $attrs): array
    {
        $maxlength = $this->getInputFieldMaxLength($input);
        // type='date' and type='time' display localized value which may be confusing,
        // type='datetime' uses 'T' as date and time separator

        if ($input->isNumber()) {
            $attrs['type'] = 'number';
        }
        if ($maxlength > 0) {
            $attrs['data-maxlength'] = $maxlength;
        }
        if ($input->bigSize($maxlength)) {
            $attrs['size'] = $maxlength > 99 ? '60' : '40';
        }

        return [
            'field' => 'input',
            'attrs' => $attrs,
            'value' => $this->utils()->html($input->value ?? ''),
        ];
    }

    /**
     * Get the input field for value
     *
     * @param ColumnDmDto $input
     * @param bool|null $autofocus
     *
     * @return array
     */
    private function getColumnValueInput(ColumnDmDto $input, bool|null $autofocus): array
    {
        // From input(array $field, $value, ?string $function, ?bool $autofocus = false) in html.inc.php
        $attrs = [
            'id' => "fields_{$input->name}",
            'name' => $input->isEnum() || $input->isSet() ?
                "input_values[{$input->name}][]" : "input_values[{$input->name}]",
        ];
        if ($input->isDisabled()) {
            $attrs['disabled'] = 'disabled';
        }
        if ($autofocus) {
            $attrs['autofocus'] = true;
        }

        // This function is implemented only for MySQL.
        // Todo: check what it actually does.
        // echo driver()->unconvertFunction($column) . " ";

        return match(true) {
            $input->isEnum() => $this->getEnumColumnInput($input, $attrs),
            $input->isBool() => $this->getBoolColumnInput($input, $attrs),
            $input->isSet() => $this->getSetColumnInput($input, $attrs),
            $this->isBlob($input) => $this->getFileColumnInput($input, $attrs),
            $input->isJson() => $this->getJsonColumnInput($input, $attrs),
            $input->editText() => $this->getTextColumnInput($input, $attrs),
            default => $this->getDefaultColumnInput($input, $attrs),
        };
    }

    /**
     * Get the input field for function
     *
     * @param ColumnDmDto $input
     *
     * @return array|null
     */
    private function getColumnFunctionInput(ColumnDmDto $input): array|null
    {
        // From html.inc.php: function input(array $field, $value, ?string $function, ?bool $autofocus = false)
        if ($input->type === 'enum' || $input->function === null) {
            return null; // No function for enum values
        }

        if (count($input->functions) < 2) {
            return [
                'label' => $this->utils()->html($input->functions[0] ?? ''),
            ];
        }

        $disabledAttr = $input->isDisabled() ? ['disabled' => 'disabled'] : [];
        return [
            'select' => [
                'attrs' => [
                    'name' => "input_functions[{$input->name}]",
                    ...$disabledAttr,
                ],
                'options' => $input->functions,
                'value' => $input->functionValue(),
            ],
        ];
    }

    /**
     * @param ColumnDmDto $input
     * @param bool|null $autofocus
     *
     * @return void
     */
    public function setColumnInputValues(ColumnDmDto $input, bool|null $autofocus): void
    {
        $input->functionInput = $this->getColumnFunctionInput($input);
        $input->valueInput = $this->getColumnValueInput($input, $autofocus);
    }
}
