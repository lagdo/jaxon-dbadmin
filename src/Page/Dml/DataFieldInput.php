<?php

namespace Lagdo\DbAdmin\Db\Page\Dml;

use Lagdo\DbAdmin\Db\Page\AppPage;
use Lagdo\DbAdmin\Driver\DriverInterface;
use Lagdo\DbAdmin\Driver\Utils\Utils;

use function count;
use function in_array;
use function is_array;
use function is_string;
use function min;
use function preg_match;
use function preg_match_all;
use function reset;
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
     * @param FieldEditEntity $editField
     * @param string $fieldName
     * @param string|null $enumValue
     *
     * @return array
     */
    private function getCheckedAttr(FieldEditEntity $editField, string $fieldName, string|null $enumValue): array
    {
        $checked = is_array($editField->value) ?
            in_array($fieldName, $editField->value) : $editField->value === $enumValue;
        return $checked ? ['checked' => 'checked'] : [];
    }

    /**
     * Get data for enum or set input field
     * 
     * @param FieldEditEntity $editField
     */
    private function itemList(FieldEditEntity $editField, array $attrs, string $default = ""): array|null
    {
        // From html.inc.php: function enum_input(string $type, string $attrs, array $field, $value, string $empty = "")
        $inputType = match($editField->type) {
            'enum' => 'radio',
            'set' => 'checkbox',
            default => null,
        };
        if ($inputType === null) {
            // Only for enums and sets
            return null;
        }

        $fieldId = $attrs['id'];
        $prefix = $editField->type === 'enum' ? 'val-' : '';
        $items = [];
        if ($editField->field->null && $prefix) {
            $checkedAttr = $this->getCheckedAttr($editField, 'null', null);
            $items[] = [
                'attrs' => [
                    'type' => $inputType,
                    ...$attrs,
                    'id' => "{$fieldId}_null",
                    'value' => 'null',
                    ...$checkedAttr,
                ],
                'label' => "<i>$default</i>",
            ];
        }

        // The length value to consider depends on the field type.
        preg_match_all("~'((?:[^']|'')*)'~", $editField->field->length, $matches);
        foreach (($matches[1] ?? []) as $enumValue) {
            $enumValue = stripcslashes(str_replace("''", "'", $enumValue));
            $fieldName = "$prefix$enumValue";
            $checkedAttr = $this->getCheckedAttr($editField, $fieldName, $enumValue);
            $items[] = [
                'attrs' => [
                    'type' => $inputType,
                    ...$attrs,
                    'id' => "{$fieldId}_{$enumValue}",
                    'value' => $this->utils->html($fieldName),
                    ...$checkedAttr,
                ],
                'label' => $this->utils->html($enumValue),
            ];
        }

        return $items;
    }

    /**
     * @param FieldEditEntity $editField
     * @param array $attrs
     *
     * @return array
     */
    private function getEnumFieldInput(FieldEditEntity $editField, array $attrs): array
    {
        // From adminer.inc.php: function editInput(?string $table, array $field, string $attrs, $value): string
        $values = [
            'type' => 'enum',
            'items' => $this->itemList($editField, $attrs, 'NULL'),
        ];

        if ($this->action === 'select') {
            $values['orig'] = [
                'attrs' => [
                    'type' => 'radio',
                    ...$attrs,
                    'value' => 'orig',
                    'checked' => 'checked',
                ],
                'label' => '<i>' . $this->utils->trans->lang('original') . '</i>',
            ];
        }

        return $values;
    }

    /**
     * @param FieldEditEntity $editField
     * @param array $attrs
     *
     * @return array
     */
    private function getSetFieldInput(FieldEditEntity $editField, array $attrs): array
    {
        if (is_string($editField->value)) {
            $editField->value = explode(",", $editField->value);
        }

        return [
            'type' => 'set',
            'items' => $this->itemList($editField, $attrs),
        ];
    }

    /**
     * @param FieldEditEntity $editField
     * @param array $attrs
     *
     * @return array
     */
    private function getBoolFieldInput(FieldEditEntity $editField, array $attrs): array
    {
        $checkedAttr = $editField->isChecked() ? ['checked' => 'checked'] : [];
        return [
            'type' => 'bool',
            'hidden' => [
                'attrs' => [
                    'type' => 'hidden',
                    ...$attrs,
                    'value' => '0',
                    'id' => '', // Unset the if value in the $attrs array
                ],
            ],
            'checkbox' => [
                'attrs' => [
                    'type' => 'checkbox',
                    ...$attrs,
                    'value' => '1',
                    ...$checkedAttr,
                ],
            ],
        ];
    }

    /**
     * @param FieldEditEntity $editField
     *
     * @return bool
     */
    private function isBlob(FieldEditEntity $editField): bool
    {
        return $this->utils->isBlob($editField->field) && $this->utils->iniBool("file_uploads");
    }

    /**
     * @param FieldEditEntity $editField
     * @param array $attrs
     *
     * @return array
     */
    private function getFileFieldInput(FieldEditEntity $editField, array $attrs): array
    {
        return [
            'type' => 'file',
            'attrs' => [
                'type' => 'file',
                'id' => $attrs['id'],
                'name' => "fields-{$editField->name}",
            ],
        ];
    }

    /**
     * @param FieldEditEntity $editField
     * @param array $attrs
     *
     * @return array
     */
    private function getJsonFieldInput(FieldEditEntity $editField, array $attrs): array
    {
        return [
            'type' => 'json',
            'attrs' => [
                ...$attrs,
                'cols' => '50',
                'rows' => '5',
                'class' => 'jush-js',
            ],
            'value' => $this->utils->str->html($editField->value),
        ];
    }

    /**
     * @param FieldEditEntity $editField
     * @param array $attrs
     * @param mixed $value
     *
     * @return array
     */
    private function getTextFieldInput(FieldEditEntity $editField, array $attrs, bool $isText): array
    {
        $fieldAttrs = $isText && $this->driver->jush() !== 'sqlite' ? [
            'cols' => '50',
            'rows' => '5',
        ] : [
            'cols' => '30',
            'rows' => min(5, substr_count($editField->value, "\n") + 1),
        ];
        return [
            'type' => 'text',
            'attrs' => [
                ...$attrs,
                ...$fieldAttrs,
            ],
            'value' => $this->utils->str->html($editField->value),
        ];
    }

    /**
     * @param FieldEditEntity $editField
     *
     * @return int
     */
    private function getInputFieldMaxLength(FieldEditEntity $editField): int
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
            ($types[$type] ? $types[$type] + ($unsigned ? 0 : 1) : 0)
        );

        return $this->driver->jush() === 'sql' &&
            $this->driver->minVersion(5.6) &&
            preg_match('~time~', $type) ?
                $maxlength += 7 : // microtime
                $maxlength;
    }

    /**
     * @param FieldEditEntity $editField
     * @param array $attrs
     *
     * @return array
     */
    private function getDefaultFieldInput(FieldEditEntity $editField, array $attrs): array
    {
        $maxlength = $this->getInputFieldMaxLength($editField);
        // type='date' and type='time' display localized value which may be confusing,
        // type='datetime' uses 'T' as date and time separator

        if ($editField->isNumber()) {
            $attrs['type'] = 'number';
        }
        $attrs['value'] = $this->utils->html($editField->value ?? '');
        if ($maxlength > 0) {
            $attrs['data-maxlength'] = $maxlength;
        }
        if ($editField->bigSize($maxlength)) {
            $attrs['size'] = $maxlength > 99 ? '60' : '40';
        }

        return [
            'type' => 'input',
            'attrs' => $attrs,
        ];
    }

    /**
     * Get the input field for value
     *
     * @param FieldEditEntity $editField
     * @param bool|null $autofocus
     *
     * @return array
     */
    private function getFieldValueInput(FieldEditEntity $editField, bool|null $autofocus): array
    {
        // From input(array $field, $value, ?string $function, ?bool $autofocus = false) in html.inc.php
        $attrs = [
            'id' => "fields_{$editField->name}",
            'name' => $editField->isEnum() || $editField->isSet() ?
                "fields[{$editField->name}][]" : "fields[{$editField->name}]",
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

            ($isText = $editField->isText()) || $editField->hasNewLine() =>
                $this->getTextFieldInput($editField, $attrs, $isText),

            default => $this->getDefaultFieldInput($editField, $attrs),
        };
    }

    /**
     * Get the input field for function
     *
     * @param FieldEditEntity $editField
     *
     * @return array|null
     */
    private function getFieldFunctionInput(FieldEditEntity $editField): array|null
    {
        // From html.inc.php: function input(array $field, $value, ?string $function, ?bool $autofocus = false)
        if ($editField->type === 'enum' || $editField->function === null) {
            return null; // No function for enum values
        }

        if (count($editField->functions) <= 1) {
            return [
                'type' => 'name',
                'label' => $this->utils->str->html(reset($editField->functions)),
            ];
        }

        $disabledAttr = $editField->isDisabled() ? ['disabled' => 'disabled'] : [];
        return [
            'type' => 'select',
            'attrs' => [
                'name' => "function[{$editField->name}]",
                ...$disabledAttr,
            ],
            'options' => $editField->functions,
            'value' => $editField->function === null || $editField->hasFunction() ? $editField->function : '',
        ];
    }

    /**
     * @param FieldEditEntity $editField
     * @param bool|null $autofocus
     *
     * @return void
     */
    public function setFieldInputValues(FieldEditEntity $editField, bool|null $autofocus): void
    {
        $editField->functionInput = $this->getFieldFunctionInput($editField);
        $editField->valueInput = $this->getFieldValueInput($editField, $autofocus);
    }
}
