<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto\Ddl;

use Lagdo\DbAdmin\Driver\Sql\Dto\ColumnAction;
use Lagdo\DbAdmin\Driver\Sql\Dto\ColumnDto;

use function array_combine;
use function array_map;
use function preg_match;

/**
 * User inputs for a table column.
 */
class ColumnFormDto
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
     * @var object
     */
    private object $values;

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
        'comment',
    ];

    /**
     * @var bool
     */
    public bool $lengthRequired = true;

    /**
     * @param ColumnDto $column
     * @param ForeignKeyDdDto|null $foreignKey
     * @param array $types
     */
    public function __construct(public readonly ColumnDto $column,
        public readonly ForeignKeyDdDto|null $foreignKey, public readonly array $types)
    {
        if (preg_match('~^CURRENT_TIMESTAMP~i', $column->onUpdate)) {
            $column->onUpdate = 'CURRENT_TIMESTAMP';
        }
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
        if ($this->currValues['generated'] === '') {
            [$attr, $value] = $this->currValues['default'] === null ?
                ['default', ''] : ['generated', 'DEFAULT'];
            $this->currValues[$attr] = $value;
        }
        $this->setForeignKeyValues();
    }

    /**
     * @return void
     */
    private function setForeignKeyValues(): void
    {
        if ($this->foreignKey === null) {
            $this->currValues['foreignKey'] = '';
            $this->currValues['fkOnUpdate'] = '';
            $this->currValues['fkOnDelete'] = '';
            // $this->currValues['fkDeferrable'] = false;
            return;
        }

        $this->currValues['foreignKey'] = $this->foreignKey->idInUi();
        $this->currValues['fkOnUpdate'] = $this->foreignKey->onUpdate;
        $this->currValues['fkOnDelete'] = $this->foreignKey->onDelete;
        // $this->currValues['fkDeferrable'] = $this->foreignKey->deferrable;
    }

    /**
     * @return void
     */
    public function reset(): void
    {
        $this->values = (object)$this->currValues;
        $this->action = ColumnAction::NONE;
    }

    /**
     * @return ColumnAction
     */
    public function action(): ColumnAction
    {
        return $this->action;
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
     * @return self
     */
    public function add(): self
    {
        $this->action = ColumnAction::ADD;
        return $this;
    }

    /**
     * @return bool
     */
    public function edited(): bool
    {
        return $this->action === ColumnAction::EDIT;
    }

    /**
     * @return self
     */
    public function change(): self
    {
        $this->action = ColumnAction::EDIT;
        return $this;
    }

    /**
     * @return self
     */
    public function changeIf(): self
    {
        $this->action = $this->columnEdited() ? ColumnAction::EDIT : ColumnAction::NONE;
        return $this;
    }

    /**
     * @return bool
     */
    public function dropped(): bool
    {
        return $this->action === ColumnAction::DROP;
    }

    /**
     * @return self
     */
    public function drop(): self
    {
        $this->action = ColumnAction::DROP;
        return $this;
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
     * @return array
     */
    public function attributes(): array
    {
        return self::$attributes;
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
    public function type(): string
    {
        return $this->values()->type;
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
     * Update with values from the user input.
     *
     * @param array $values
     *
     * @return static
     */
    public function updateValues(array $values): static
    {
        $this->action = ColumnAction::convert($values['action']);
        $this->position = $values['position'];
        $this->setValues($values['column']);

        return $this;
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

    /**
     * @return bool
     */
    public function hasForeignKey(): bool
    {
        return $this->values()->foreignKey !== '' || $this->foreignKey !== null;
    }

    /**
     * @return bool
     */
    public function fkAdded(): bool
    {
        return $this->added() ||
            ($this->values()->foreignKey !== '' && $this->foreignKey === null);
    }

    /**
     * @return bool
     */
    public function fkEdited(): bool
    {
        $values = $this->values();
        return $values->foreignKey !== '' && $this->foreignKey !== null &&
            ($values->foreignKey !== $this->foreignKey->idInUi() ||
            // $values->fkDeferrable !== $this->foreignKey->deferrable ||
            $values->fkOnUpdate !== $this->foreignKey->onUpdate ||
            $values->fkOnDelete !== $this->foreignKey->onDelete);
    }

    /**
     * @return bool
     */
    public function fkDropped(): bool
    {
        return $this->dropped() ||
            ($this->values()->foreignKey === '' && $this->foreignKey !== null);
    }

    /**
     * The id to be used in dropdown
     *
     * @return string
     */
    public function fkIdValue(): string
    {
        return $this->values()->foreignKey;
    }

    /**
     * @return string
     */
    public function fkId(): string
    {
        return $this->values()->foreignKey ?: $this->foreignKey?->idInUi() ?? '';
    }

    /**
     * @return string
     */
    public function fkOnUpdate(): string
    {
        return $this->values()->foreignKey !== '' ?
            $this->values()->fkOnUpdate : $this->foreignKey?->onUpdate ?? '';
    }

    /**
     * @return string
     */
    public function fkOnDelete(): string
    {
        return $this->values()->foreignKey !== '' ?
            $this->values()->fkOnDelete : $this->foreignKey?->onDelete ?? '';
    }

    /**
     * @return bool
     */
    public function fkDeferrable(): bool
    {
        return $this->values()->foreignKey !== '' ?
            $this->values()->fkDeferrable : $this->foreignKey?->deferrable ?? false;
    }
}
