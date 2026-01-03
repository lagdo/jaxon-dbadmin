<?php

namespace Lagdo\DbAdmin\Db\Page\Ddl;

use Lagdo\DbAdmin\Driver\Entity\TableFieldEntity;

class ColumnEntity
{
    /**
     * The unchanged name of the table field
     *
     * @var string
     */
    public $name = '';

    /**
     * The field status when the table is edited
     *
     * @var string
     */
    public $status = 'unchanged';

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
    public function __construct(private TableFieldEntity $field)
    {
        $this->name = $field->name;
        // Make sure the boolean fields have boolean values.
        $this->field->primary = (bool)$this->field->primary;
        $this->field->autoIncrement = (bool)$this->field->autoIncrement;
        $this->field->nullable = (bool)$this->field->nullable;
        // Don't keep null in the comment value.
        $this->field->comment ??= '';
    }

    /**
     * @return TableFieldEntity
     */
    public function field(): TableFieldEntity
    {
        return $this->field;
    }

    /**
     * @return object
     */
    public function fieldValues(): object
    {
        return (object)[
            'name' => $this->field->name,
            'primary' => $this->field->primary,
            'autoIncrement' => $this->field->autoIncrement,
            'type' => $this->field->type,
            'unsigned' => $this->field->unsigned,
            'hasDefault' => $this->field->hasDefault(),
            'default' => $this->field->default ?? '',
            'length' => $this->field->length,
            'nullable' => $this->field->nullable,
            'collation' => $this->field->collation,
            'onUpdate' => $this->field->onUpdate,
            'onDelete' => $this->field->onDelete,
            'comment' => $this->field->comment,
        ];
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
        $this->values = (object)$values;
    }

    private function sameDefault(): bool
    {
        $values = $this->values();
        return !$this->field->hasDefault() ? !$values->hasDefault :
            $values->hasDefault && $values->default === $this->field->default ?? '';
    }

    /**
     * @return bool
     */
    public function fieldEdited(): bool
    {
        $values = $this->values();
        return !$this->sameDefault() ||
            $values->name !== $this->field->name ||
            $values->primary !== $this->field->primary ||
            $values->autoIncrement !== $this->field->autoIncrement ||
            $values->type !== $this->field->type ||
            $values->unsigned !== $this->field->unsigned ||
            $values->length !== $this->field->length ||
            $values->nullable !== $this->field->nullable ||
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
     * @return bool
     */
    private function defaultValueChanged(): bool
    {
        $values = $this->values();
        $fieldHasDefault = $this->field->hasDefault();
        return $values->hasDefault && (!$fieldHasDefault || $values->default !== $this->field->default);
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
        if ($values->hasDefault !== $this->field->hasDefault()) {
            $changes['has default'] = [
                'from' => $this->field->hasDefault() ? 'true' : 'false',
                'to' => $values->hasDefault ? 'true' : 'false',
            ];
        }
        if ($this->defaultValueChanged()) {
            $changes['default'] = [
                'from' => $this->field->default,
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
            'status' => $this->status,
            'position' => $this->position,
            'field' => $this->values(),
        ];
    }

    /**
     * Create an entity from js app data
     *
     * @param TableFieldEntity $field
     * @param array $columnData
     *
     * @return ColumnEntity
     */
    public static function make(TableFieldEntity $field, array $columnData): self
    {
        // Pass the field to the constructor, so the origValues attr is set properly. 
        $column = new static($field);
        $column->name = $columnData['name'];
        $column->status = $columnData['status'];
        $column->position = $columnData['position'];
        $column->setValues($columnData['field']);

        return $column;
    }
}
