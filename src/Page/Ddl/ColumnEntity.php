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
        // Make sure the boolean fields are booleans.
        $this->field->primary = (bool)$this->field->primary;
        $this->field->autoIncrement = (bool)$this->field->autoIncrement;
        $this->field->nullable = (bool)$this->field->nullable;
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
    public function values(): object
    {
        return $this->values ??= (object)[
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
     * @param array $values
     *
     * @return void
     */
    public function updateField(array $values): void
    {
        $this->values = (object)$values;
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
        $column->updateField($columnData['field']);

        return $column;
    }
}
