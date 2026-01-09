<?php

namespace Lagdo\DbAdmin\Db\UiData\Dml;

use Lagdo\DbAdmin\Db\UiData\AppPage;
use Lagdo\DbAdmin\Driver\DriverInterface;
use Lagdo\DbAdmin\Driver\Utils\Utils;

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
class DataFieldInput
{
    /**
     * The constructor
     *
     * @param AppPage $page
     * @param DriverInterface $driver
     * @param Utils $utils
     * @param string $action
     * @param string $operation
     */
    public function __construct(private AppPage $page, private DriverInterface $driver,
        private Utils $utils, private string $action, private string $operation)
    {}

    /**
     * @param FieldEditDto $editField
     * @param string $fieldValue
     * @param string|null $enumValue
     *
     * @return bool
     */
    private function isChecked(FieldEditDto $editField, string $fieldValue, string|null $enumValue): bool
    {
        return !is_array($editField->value) ? $editField->value === $enumValue :
            in_array($fieldValue, $editField->value);
    }

    /**
     * Get data for enum or set input field
     * 
     * @param FieldEditDto $editField
     */
    private function getItemList(FieldEditDto $editField, array $attrs, string $default = ""): array|null
    {
        if ($editField->type !== 'enum' && $editField->type !== 'set' ) {
            // Only for enums and sets
            return null;
        }

        // From html.inc.php: function enum_input(string $type, string $attrs, array $field, $value, string $empty = "")
        $prefix = $editField->type === 'enum' ? 'val-' : '';
        $items = [];
        if ($editField->field->nullable && $prefix) {
            $items[] = [
                'attrs' => [
                    ...$attrs,
                    'id' => "{$attrs['id']}_null", // Overwrite the id value in the $attrs array.
                ],
                'label' => "<i>$default</i>",
                'value' => 'null',
                'checked' => $this->isChecked($editField, 'null', null),
            ];
        }

        preg_match_all("~'((?:[^']|'')*)'~", $editField->field->length, $matches);
        foreach (($matches[1] ?? []) as $enumValue) {
            $enumValue = stripcslashes(str_replace("''", "'", $enumValue));
            $fieldValue = "$prefix$enumValue";
            $items[] = [
                'attrs' => [
                    ...$attrs,
                    'id' => "{$attrs['id']}_{$enumValue}", // Overwrite the id value in the $attrs array.
                ],
                'label' => $this->utils->html($enumValue),
                'value' => $this->utils->html($fieldValue),
                'checked' => $this->isChecked($editField, $fieldValue, $enumValue),
            ];
        }

        return $items;
    }

    /**
     * @param FieldEditDto $editField
     * @param array $attrs
     *
     * @return array
     */
    private function getEnumFieldInput(FieldEditDto $editField, array $attrs): array
    {
        // From adminer.inc.php: function editInput(?string $table, array $field, string $attrs, $value): string
        $items = $this->getItemList($editField, $attrs, 'NULL');
        if ($this->action === 'select') {
            // Prepend the value to the item list
            $items = [[
                'attrs' => $attrs,
                'label' => '<i>' . $this->utils->trans->lang('original') . '</i>',
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
     * @param FieldEditDto $editField
     * @param array $attrs
     *
     * @return array
     */
    private function getSetFieldInput(FieldEditDto $editField, array $attrs): array
    {
        if (is_string($editField->value)) {
            $editField->value = explode(",", $editField->value);
        }

        return [
            'field' => 'set',
            'items' => $this->getItemList($editField, $attrs),
        ];
    }

    /**
     * @param FieldEditDto $editField
     * @param array $attrs
     *
     * @return array
     */
    private function getBoolFieldInput(FieldEditDto $editField, array $attrs): array
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
            'checked' => $editField->isChecked(),
        ];
    }

    /**
     * @param FieldEditDto $editField
     *
     * @return bool
     */
    private function isBlob(FieldEditDto $editField): bool
    {
        return $this->utils->isBlob($editField->field) && $this->utils->iniBool("file_uploads");
    }

    /**
     * @param FieldEditDto $editField
     * @param array $attrs
     *
     * @return array
     */
    private function getFileFieldInput(FieldEditDto $editField, array $attrs): array
    {
        return [
            'field' => 'file',
            'attrs' => [
                'id' => $attrs['id'],
                'name' => "fields-{$editField->name}",
            ],
        ];
    }

    /**
     * @param FieldEditDto $editField
     * @param array $attrs
     *
     * @return array
     */
    private function getJsonFieldInput(FieldEditDto $editField, array $attrs): array
    {
        return [
            'field' => 'json',
            'attrs' => [
                ...$attrs,
                'cols' => '50',
                'rows' => '5',
                'class' => 'jush-js',
            ],
            'value' => $this->utils->str->html($editField->value ?? ''),
        ];
    }

    /**
     * @param FieldEditDto $editField
     *
     * @return bool
     */
    private function textSizeIsFixed(FieldEditDto $editField): bool
    {
        return ($editField->isText() && $this->driver->jush() !== 'sqlite') || $editField->isSearch();
    }

    /**
     * @param FieldEditDto $editField
     * @param array $attrs
     *
     * @return array
     */
    private function getTextFieldInput(FieldEditDto $editField, array $attrs): array
    {
        $fieldAttrs = $this->textSizeIsFixed($editField) ? [
            'cols' => '50',
            'rows' => '5',
        ] : [
            'cols' => '30',
            'rows' => min(5, substr_count($editField->value ?? '', "\n") + 1),
        ];
        return [
            'field' => 'text',
            'attrs' => [
                ...$attrs,
                ...$fieldAttrs,
            ],
            'value' => $this->utils->html($editField->value ?? ''),
        ];
    }

    /**
     * @param FieldEditDto $editField
     *
     * @return int
     */
    private function getInputFieldMaxLength(FieldEditDto $editField): int
    {
        $unsigned = $editField->field->unsigned;
        $length = $editField->field->length;
        $type = $editField->type;
        $types = $this->driver->types();

        $maxlength = (!preg_match('~int~', $type) &&
            preg_match('~^(\d+)(,(\d+))?$~', $length, $match) ?
            ((preg_match("~binary~", $type) ? 2 : 1) *
                ($match[1] ?? 0) + (($match[3] ?? false) ? 1 : 0) +
                (($match[2] ?? false) && !$unsigned ? 1 : 0)) :
            (isset($types[$type]) ? $types[$type] + ($unsigned ? 0 : 1) : 0)
        );

        return $this->driver->jush() === 'sql' &&
            $this->driver->minVersion(5.6) &&
            preg_match('~time~', $type) ?
                $maxlength += 7 : // microtime
                $maxlength;
    }

    /**
     * @param FieldEditDto $editField
     * @param array $attrs
     *
     * @return array
     */
    private function getDefaultFieldInput(FieldEditDto $editField, array $attrs): array
    {
        $maxlength = $this->getInputFieldMaxLength($editField);
        // type='date' and type='time' display localized value which may be confusing,
        // type='datetime' uses 'T' as date and time separator

        if ($editField->isNumber()) {
            $attrs['type'] = 'number';
        }
        if ($maxlength > 0) {
            $attrs['data-maxlength'] = $maxlength;
        }
        if ($editField->bigSize($maxlength)) {
            $attrs['size'] = $maxlength > 99 ? '60' : '40';
        }

        return [
            'field' => 'input',
            'attrs' => $attrs,
            'value' => $this->utils->html($editField->value ?? ''),
        ];
    }

    /**
     * Get the input field for value
     *
     * @param FieldEditDto $editField
     * @param bool|null $autofocus
     *
     * @return array
     */
    private function getFieldValueInput(FieldEditDto $editField, bool|null $autofocus): array
    {
        // From input(array $field, $value, ?string $function, ?bool $autofocus = false) in html.inc.php
        $attrs = [
            'id' => "fields_{$editField->name}",
            'name' => $editField->isEnum() || $editField->isSet() ?
                "field_values[{$editField->name}][]" : "field_values[{$editField->name}]",
        ];
        if ($editField->isDisabled()) {
            $attrs['disabled'] = 'disabled';
        }
        if ($autofocus) {
            $attrs['autofocus'] = true;
        }

        // This function is implemented only for MySQL.
        // Todo: check what it actually does.
        // echo driver()->unconvertFunction($field) . " ";

        return match(true) {
            $editField->isEnum() => $this->getEnumFieldInput($editField, $attrs),
            $editField->isBool() => $this->getBoolFieldInput($editField, $attrs),
            $editField->isSet() => $this->getSetFieldInput($editField, $attrs),
            $this->isBlob($editField) => $this->getFileFieldInput($editField, $attrs),
            $editField->isJson() => $this->getJsonFieldInput($editField, $attrs),
            $editField->editText() => $this->getTextFieldInput($editField, $attrs),
            default => $this->getDefaultFieldInput($editField, $attrs),
        };
    }

    /**
     * Get the input field for function
     *
     * @param FieldEditDto $editField
     *
     * @return array|null
     */
    private function getFieldFunctionInput(FieldEditDto $editField): array|null
    {
        // From html.inc.php: function input(array $field, $value, ?string $function, ?bool $autofocus = false)
        if ($editField->type === 'enum' || $editField->function === null) {
            return null; // No function for enum values
        }

        if (count($editField->functions) < 2) {
            return [
                'label' => $this->utils->str->html($editField->functions[0] ?? ''),
            ];
        }

        $disabledAttr = $editField->isDisabled() ? ['disabled' => 'disabled'] : [];
        return [
            'select' => [
                'attrs' => [
                    'name' => "field_functions[{$editField->name}]",
                    ...$disabledAttr,
                ],
                'options' => $editField->functions,
                'value' => $editField->functionValue(),
            ],
        ];
    }

    /**
     * @param FieldEditDto $editField
     * @param bool|null $autofocus
     *
     * @return void
     */
    public function setFieldInputValues(FieldEditDto $editField, bool|null $autofocus): void
    {
        $editField->functionInput = $this->getFieldFunctionInput($editField);
        $editField->valueInput = $this->getFieldValueInput($editField, $autofocus);
    }
}
