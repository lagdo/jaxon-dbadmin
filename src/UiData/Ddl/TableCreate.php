<?php

namespace Lagdo\DbAdmin\Db\UiData\Ddl;

use Lagdo\DbAdmin\Db\UiData\AppPage;
use Lagdo\DbAdmin\Driver\DriverInterface;
use Lagdo\DbAdmin\Driver\Dto\ForeignKeyDto;
use Lagdo\DbAdmin\Driver\Dto\TableCreateDto;
use Lagdo\DbAdmin\Driver\Utils\Utils;

use function array_filter;
use function count;

class TableCreate
{
    use ForeignKeyTrait;

    /**
     * The constructor
     *
     * @param AppPage $page
     * @param DriverInterface $driver
     * @param Utils $utils
     */
    public function __construct(private AppPage $page,
        private DriverInterface $driver, private Utils $utils)
    {}

    /**
     * @param TableCreateDto $table
     * @param array<ColumnInputDto> $columns
     * 
     * @return TableCreateDto
     */
    public function makeDto(TableCreateDto $table, array $columns): TableCreateDto
    {
        // From create.inc.php
        $this->getForeignKeys();

        // Auto increment
        $aiCount = count(array_filter($columns, fn($column) => $column->field->autoIncrement));
        if ($aiCount > 1) {
            $table->error = $this->utils->trans->lang('Only one auto-increment field is allowed.');
            return $table;
        }

        $referencableFields = $this->referencableFields();
        // $after = " FIRST";

        $table->clearColumns();
        foreach ($columns as $column) {
            $inputField = $column->inputField();
            $foreignKey = $this->foreignKeys[$inputField->type] ?? null;
            //! can collide with user defined type
            $typeField = $foreignKey !== null ? $referencableFields[$foreignKey] : $inputField;

            $input = $this->driver->getFieldClauses($inputField, $typeField);
            // $input->after = $after;

            if ($foreignKey !== null) {
                $fkField = new ForeignKeyDto();
                $fkField->table = $foreignKey;
                $fkField->source = [$inputField->name];
                $fkField->target = [$typeField->name];
                $fkField->onDelete = $inputField->onDelete;
                $table->foreignKeys[$inputField->name] = $fkField;
            }

            $table->columns[] = $input;
            // $after = " AFTER " . $this->driver->escapeId($inputField->name);
        }

        return $table;
    }
}
