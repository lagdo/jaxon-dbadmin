<?php

namespace Lagdo\DbAdmin\Db\UiData\Ddl;

use Lagdo\DbAdmin\Driver\Dto\TableFieldDto;

use function array_combine;
use function array_map;

/**
 * User inputs for a table column.
 */
class ColumnInputDto
{
    /**
     * The unchanged name of the table field
     *
     * @var string
     */
    public readonly string $name;

    /**
     * The field status when the table is edited
     *
     * @var string
     */
    private $action = 'none';

    /**
     * The field position in the edit form
     *
     * @var int
     */
    public $position = 0;

    /**
     * The original or edited field values
     *
     * @var object|null
     */
    private $values = null;

    /**
     * The attributes in the column values
     *
     * @var array
     */
    private static $attributes = [
        'name',
        'primary',
        'autoIncrement',
        'type',
        'unsigned',
        'generated',
        'default',
        'length',
        'nullable',
        'collation',
        'onUpdate',
        'onDelete',
        'comment',
    ];

    /**
     * The constructor
     *
     * @param TableFieldDto $field
     */
    public function __construct(public readonly TableFieldDto $field)
    {
        $this->name = $field->name;
        // Make sure the boolean fields have boolean values.
        $this->field->primary = (bool)$this->field->primary;
        $this->field->autoIncrement = (bool)$this->field->autoIncrement;
        $this->field->nullable = (bool)$this->field->nullable;
        // Don't keep null in the comment value.
        $this->field->comment ??= '';

        // Set the "DEFAULT" value for the "generated" attribute.
        // Remove the null value from the "default" attribute.
        // From create.inc.php
        if ($this->field->generated === '') {
            if ($this->field->default === null) {
                $this->field->default = '';
            } else {
                $this->field->generated = 'DEFAULT';
            }
        }
    }

    /**
     * @return void
     */
    public function undo(): void
    {
        $this->action = 'none';
    }

    /**
     * @return bool
     */
    public function unchanged(): bool
    {
        return $this->action === 'none';
    }

    /**
     * @return bool
     */
    public function added(): bool
    {
        return $this->action === 'add';
    }

    /**
     * @return void
     */
    public function add(): void
    {
        $this->action = 'add';
    }

    /**
     * @return bool
     */
    public function changed(): bool
    {
        return $this->action === 'change';
    }

    /**
     * @return void
     */
    public function change(): void
    {
        $this->action = 'change';
    }

    /**
     * @return void
     */
    public function changeIf(): void
    {
        $this->action = $this->fieldEdited() ? 'change' : 'none';
    }

    /**
     * @return bool
     */
    public function dropped(): bool
    {
        return $this->action === 'drop';
    }

    /**
     * @return void
     */
    public function drop(): void
    {
        $this->action = 'drop';
    }

    /**
     * @return array
     */
    public function fieldValues(): array
    {
        return array_combine(self::$attributes, array_map(fn(string $attr) =>
            $this->field->$attr, self::$attributes));
    }

    /**
     * @return object
     */
    public function values(): object
    {
        return $this->values ??= (object)$this->fieldValues();
    }

    /**
     * @param array $values
     *
     * @return void
     */
    public function setValues(array $values): void
    {
        if ($values['generated'] === '') {
            $values['default'] = '';
        }
        $this->values = (object)$values;
    }

    /**
     * @return bool
     */
    public function fieldEdited(): bool
    {
        $values = $this->values();
        foreach (self::$attributes as $attr) {
            if ($values->$attr !== $this->field->$attr) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return string
     */
    public function newName(): string
    {
        return $this->values()->name ?: '(No name)';
    }

    /**
     * @return array
     */
    public function changes(): array
    {
        $changes = [];
        $values = $this->values();

        // The first attributes
        foreach (['name', 'type', 'unsigned', 'length'] as $attr) {
            if ($values->$attr !== $this->field->$attr) {
                $changes[$attr] = [
                    'from' => $this->field->$attr,
                    'to' => $values->$attr,
                ];
            }
        }
        // The boolean attributes
        foreach (['primary', 'autoIncrement', 'nullable'] as $attr) {
            if ($values->$attr !== $this->field->$attr) {
                $changes[$attr] = [
                    'from' => $this->field->$attr ? 'true' : 'false',
                    'to' => $values->$attr ? 'true' : 'false',
                ];
            }
        }
        // The default value
        if ($values->generated !== $this->field->generated) {
            $changes['generated'] = [
                'from' => $this->field->generated,
                'to' => $values->generated,
            ];
        }
        $default = $this->field->default ?? '';
        if ($values->default !== $default) {
            $changes['default'] = [
                'from' => $default,
                'to' => $values->default,
            ];
        }
        // The other attributes
        foreach (['collation', 'onUpdate', 'onDelete', 'comment'] as $attr) {
            if ($values->$attr !== $this->field->$attr) {
                $changes[$attr] = [
                    'from' => $this->field->$attr,
                    'to' => $values->$attr,
                ];
            }
        }

        return $changes;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'action' => $this->action,
            'position' => $this->position,
            'field' => $this->values(),
        ];
    }

    /**
     * @return TableFieldDto
     */
    public function inputField(): TableFieldDto
    {
        $values = $this->values();
        $field = new TableFieldDto();

        foreach (self::$attributes as $attr) {
            $field->$attr = $values->$attr;
        }
        if ($values->generated === '') {
            $field->default = null;
        }

        return $field;
    }

    /**
     * Create an entity from user inputs.
     *
     * @param TableFieldDto $field
     * @param array $inputs
     *
     * @return ColumnInputDto
     */
    public static function newColumn(TableFieldDto $field, array $inputs): self
    {
        // Pass the field to the constructor, so the origValues attr is set properly. 
        $column = new static($field);
        $column->action = $inputs['action'];
        $column->position = $inputs['position'];
        $column->setValues($inputs['field']);

        return $column;
    }

    /**
     * Check the action in the user inputs.
     *
     * @param array $column
     *
     * @return bool
     */
    public static function columnIsAdded(array $column): bool
    {
        return $column['action'] === 'add';
    }
}
