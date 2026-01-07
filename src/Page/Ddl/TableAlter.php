<?php

namespace Lagdo\DbAdmin\Db\Page\Ddl;

use Lagdo\DbAdmin\Db\Page\AppPage;
use Lagdo\DbAdmin\Driver\DriverInterface;
use Lagdo\DbAdmin\Driver\Entity\ForeignKeyEntity;
use Lagdo\DbAdmin\Driver\Entity\TableAlterEntity;
use Lagdo\DbAdmin\Driver\Utils\Utils;

use function array_filter;
use function count;

class TableAlter
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
     * @param TableAlterEntity $table
     * @param array<ColumnInputEntity> $columns
     * 
     * @return TableAlterEntity
     */
    public function makeEntity(TableAlterEntity $table, array $columns): TableAlterEntity
    {
        // From create.inc.php
        $this->getForeignKeys($table->name);

        // Auto increment
        $aiCount = count(array_filter($columns, fn(ColumnInputEntity $column) =>
            $column->field()->autoIncrement));
        if ($aiCount > 1) {
            $table->error = $this->utils->trans->lang('Only one auto-increment field is allowed.');
            return $table;
        }

        // Todo: move fields up and down

        $referencableFields = $this->referencableFields($table->name);
        // $after = " FIRST";

        $table->clearColumns();
        foreach ($columns as $column) {
            if ($column->unchanged()) {
                continue;
            }
            if ($column->dropped()) {
                $table->droppedColumns[] = $column->name;
                continue;
            }

            $inputField = $column->inputField();
            $foreignKey = $this->foreignKeys[$inputField->type] ?? null;
            //! can collide with user defined type
            $typeField = $foreignKey !== null ? $referencableFields[$foreignKey] : $inputField;

            $input = $this->driver->getFieldClauses($inputField, $typeField);
            // $input->after = $after;

            if ($foreignKey !== null) {
                $fkField = new ForeignKeyEntity();
                $fkField->table = $foreignKey;
                $fkField->source = [$inputField->name];
                $fkField->target = [$typeField->name];
                $fkField->onDelete = $inputField->onDelete;
                $table->foreignKeys[$inputField->name] = $fkField;
            }

            $column->added() ? $table->addedColumns[] = $input :
                $table->changedColumns[$column->name] = $input;
            // $after = " AFTER " . $this->driver->escapeId($inputField->name);
        }

        return $table;
    }
}
