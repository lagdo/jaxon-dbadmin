<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto\Ddl;

use Lagdo\DbAdmin\Driver\Sql\Dto\TableDto;

use function array_combine;
use function array_map;

/**
 * User inputs for a table.
 */
class TableFormDto
{
    /**
     * The current table values
     *
     * @var array
     */
    private array $currValues;

    /**
     * The user input values for the table
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
        'hasAutoIncrement',
        'autoIncrement',
        'collation',
        'engine',
        'comment',
    ];

    /**
     * @var string
     */
    public string $autoIncrementColumn = '';

    /**
     * @param TableDto $status
     * @param array<ColumnFormDto> $columns
     */
    public function __construct(public readonly TableDto $status, public array $columns)
    {
        // Copy the table column values into the local $currValues array.
        $this->currValues = array_combine(self::$attributes,
            array_map(fn(string $attr) => $status->$attr, self::$attributes));
        $this->currValues['setComment'] = false;

        foreach ($columns as $column) {
            if ($column->values()->autoIncrement) {
                $this->autoIncrementColumn = $column->values()->name;
                break;
            }
        }
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

    /**
     * @return object
     */
    public function values(): object
    {
        return $this->values ??= (object)$this->currValues;
    }
}
