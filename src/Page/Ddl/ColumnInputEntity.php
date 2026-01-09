<?php

namespace Lagdo\DbAdmin\Db\Page\Ddl;

use Lagdo\DbAdmin\Driver\Entity\TableFieldEntity;

/**
 * User inputs for a table column.
 */
class ColumnInputEntity
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
     * The constructor
     *
     * @param TableFieldEntity $field
     */
    public function __construct(public readonly TableFieldEntity $field)
    {
        $this->name = $field->name;
        // Make sure the boolean fields have boolean values.
        $this->field->primary = (bool)$this->field->primary;
        $this->field->autoIncrement = (bool)$this->field->autoIncrement;
        $this->field->nullable = (bool)$this->field->nullable;
        // Don't keep null in the comment value.
        $this->field->comment ??= '';

        // Set the "DEFAULT" value for the "generated" field.
        // From create.inc.php
        if ($this->field->generated === '' && $this->field->default !== null) {
            $this->field->generated = 'DEFAULT';
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
     * Convert the field values from array to object.
     *
     * @param array $values
     *
     * @return object
     */
    private function convertValues(array $values): object
    {
        if ($values['generated'] === '') {
            $values['default'] = '';
        }
        return (object)$values;
    }

    /**
     * @return object
     */
    public function fieldValues(): object
    {
        return $this->convertValues([
            'name' => $this->field->name,
            'primary' => $this->field->primary,
            'autoIncrement' => $this->field->autoIncrement,
            'type' => $this->field->type,
            'unsigned' => $this->field->unsigned,
            'generated' => $this->field->generated,
            'default' => $this->field->default ?? '',
            'length' => $this->field->length,
            'nullable' => $this->field->nullable,
            'collation' => $this->field->collation,
            'onUpdate' => $this->field->onUpdate,
            'onDelete' => $this->field->onDelete,
            'comment' => $this->field->comment,
        ]);
    }

    /**
     * @return object
     */
    public function values(): object
    {
        return $this->values ??= $this->fieldValues();
    }

    /**
     * @param array $values
     *
     * @return void
     */
    public function setValues(array $values): void
    {
        $this->values = $this->convertValues($values);
    }

    /**
     * @return bool
     */
    public function fieldEdited(): bool
    {
        $values = $this->values();
        return $values->name !== $this->field->name ||
            $values->primary !== $this->field->primary ||
            $values->autoIncrement !== $this->field->autoIncrement ||
            $values->type !== $this->field->type ||
            $values->unsigned !== $this->field->unsigned ||
            $values->length !== $this->field->length ||
            $values->nullable !== $this->field->nullable ||
            $values->generated !== $this->field->generated ||
            $values->default !== ($this->field->default ?? '') ||
            $values->collation !== $this->field->collation ||
            $values->onUpdate !== $this->field->onUpdate ||
            $values->onDelete !== $this->field->onDelete ||
            $values->comment !== $this->field->comment;
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
     * @return TableFieldEntity
     */
    public function inputField(): TableFieldEntity
    {
        $values = $this->values();
        $field = new TableFieldEntity();

        $field->name = $values->name;
        $field->primary = $values->primary;
        $field->autoIncrement = $values->autoIncrement;
        $field->type = $values->type;
        $field->unsigned = $values->unsigned;
        $field->generated = $values->generated;
        $field->default = $values->generated !== '' ? $values->default : null;
        $field->length = $values->length;
        $field->nullable = $values->nullable;
        $field->collation = $values->collation;
        $field->onUpdate = $values->onUpdate;
        $field->onDelete = $values->onDelete;
        $field->comment = $values->comment;

        return $field;
    }

    /**
     * Create an entity from user inputs.
     *
     * @param TableFieldEntity $field
     * @param array $inputs
     *
     * @return ColumnInputEntity
     */
    public static function newColumn(TableFieldEntity $field, array $inputs): self
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
