<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto\Ddl;

use Lagdo\DbAdmin\Driver\Sql\Dto\ColumnDto;

use function array_combine;
use function array_map;

/**
 * User inputs for a table column.
 */
class DdInputDto
{
    /**
     * The column status when the table is edited
     *
     * @var ColumnAction
     */
    private ColumnAction $action = ColumnAction::NONE;

    /**
     * The column position in the edit form
     *
     * @var int
     */
    public int $position = 0;

    /**
     * The current column values
     *
     * @var array
     */
    private array $currValues;

    /**
     * The user input values for the column
     *
     * @var object|null
     */
    private object|null $values = null;

    /**
     * The attributes in the input values
     *
     * @var array
     */
    private static array $attributes = [
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
     * @param ColumnDto $column
     */
    public function __construct(public readonly ColumnDto $column)
    {
        // Copy the table column values into the local $currValues array.
        $this->currValues = array_combine(self::$attributes,
            array_map(fn(string $attr) => $column->$attr, self::$attributes));
        // Make sure the boolean columns have boolean values.
        $this->currValues['primary'] = (bool)$this->currValues['primary'];
        $this->currValues['autoIncrement'] = (bool)$this->currValues['autoIncrement'];
        $this->currValues['nullable'] = (bool)$this->currValues['nullable'];
        // Don't keep the null value in the comment.
        $this->currValues['comment'] ??= '';
        $this->currValues['setComment'] = false;
        // Set the "DEFAULT" value for the "generated" attribute.
        // Remove the null value from the "default" attribute.
        // From create.inc.php
        if ($this->currValues['generated'] === '') {
            [$attr, $value] = $this->currValues['default'] === null ?
                ['default', ''] : ['generated', 'DEFAULT'];
            $this->currValues[$attr] = $value;
        }
    }

    /**
     * @return void
     */
    public function undo(): void
    {
        $this->action = ColumnAction::NONE;
    }

    /**
     * @return bool
     */
    public function unchanged(): bool
    {
        return $this->action === ColumnAction::NONE;
    }

    /**
     * @return bool
     */
    public function added(): bool
    {
        return $this->action === ColumnAction::ADD;
    }

    /**
     * @return void
     */
    public function add(): void
    {
        $this->action = ColumnAction::ADD;
    }

    /**
     * @return bool
     */
    public function changed(): bool
    {
        return $this->action === ColumnAction::CHANGE;
    }

    /**
     * @return void
     */
    public function change(): void
    {
        $this->action = ColumnAction::CHANGE;
    }

    /**
     * @return void
     */
    public function changeIf(): void
    {
        $this->action = $this->columnEdited() ? ColumnAction::CHANGE : ColumnAction::NONE;
    }

    /**
     * @return bool
     */
    public function dropped(): bool
    {
        return $this->action === ColumnAction::DROP;
    }

    /**
     * @return void
     */
    public function drop(): void
    {
        $this->action = ColumnAction::DROP;
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
     * @return object
     */
    public function values(): object
    {
        return $this->values ??= (object)$this->currValues;
    }

    /**
     * @return bool
     */
    public function columnEdited(): bool
    {
        $values = $this->values();
        foreach (self::$attributes as $attr) {
            if ($values->$attr !== $this->currValues[$attr]) {
                return true;
            }
        }
        // The setComment column meaning is different.
        return $values->setComment;
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
            if ($values->$attr !== $this->currValues[$attr]) {
                $changes[$attr] = [
                    'from' => $this->currValues[$attr],
                    'to' => $values->$attr,
                ];
            }
        }
        // The boolean attributes
        foreach (['primary', 'autoIncrement', 'nullable'] as $attr) {
            if ($values->$attr !== $this->currValues[$attr]) {
                $changes[$attr] = [
                    'from' => $this->currValues[$attr] ? 'true' : 'false',
                    'to' => $values->$attr ? 'true' : 'false',
                ];
            }
        }
        // The string attributes
        foreach (['generated', 'default', 'collation', 'onUpdate', 'onDelete', 'comment'] as $attr) {
            if ($values->$attr !== $this->currValues[$attr]) {
                $changes[$attr] = [
                    'from' => $this->currValues[$attr],
                    'to' => $values->$attr,
                ];
            }
        }
        // The comment attribute
        if ($values->setComment) {
            $changes['comment'] = [
                'from' => $this->currValues['comment'] ?? '(NULL)',
                'to' => $values->comment,
            ];
        }

        return $changes;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->column->name,
            'action' => $this->action,
            'position' => $this->position,
            'column' => $this->values(),
        ];
    }

    /**
     * @return ColumnDto
     */
    public function makeColumn(): ColumnDto
    {
        $column = new ColumnDto();

        $values = $this->values();
        foreach (self::$attributes as $attr) {
            $column->$attr = $values->$attr;
        }
        if ($values->generated === '') {
            $column->default = null;
        }

        return $column;
    }

    /**
     * Create an entity from user input.
     *
     * @param ColumnDto $column
     * @param array $values
     *
     * @return static
     */
    public static function newColumn(ColumnDto $column, array $values): self
    {
        $input = new static($column);

        $input->action = ColumnAction::convert($values['action']);
        $input->position = $values['position'];
        $input->setValues($values['column']);

        return $input;
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
        return ColumnAction::equalsAdd($column['action']);
    }
}
