<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Ddl\Column;

use Lagdo\DbAdmin\Db\Page\Ddl\ColumnInputEntity;

use function array_filter;
use function array_map;

class DeleteFunc extends FuncComponent
{
    /**
     * @param array<ColumnInputEntity> $columns
     * @param string $fieldName
     *
     * @return array
     */
    private function updateColumns(array $columns, string $fieldName): array
    {
        $column = $columns[$fieldName];
        if (!$column->added()) {
            // An existing column is set to be dropped.
            $column->drop();
            return $columns;
        }

        // Remove the column.
        return array_filter($columns, fn($c) => $c->name !== $column->name);
    }

    /**
     * @param string $fieldName
     *
     * @return void
     */
    public function exec(string $fieldName): void
    {
        $columns = $this->getTableColumns();
        if (!isset($columns[$fieldName])) {
            $table = $this->getTableName();
            $this->alert()
                ->title($this->trans->lang('Error'))
                ->error("Unable to find the field with '$fieldName' in table '$table'.");
            return;
        }

        $columns = $this->updateColumns($columns, $fieldName);
        $this->cl(Table::class)->show($this->metadata(), $columns);
    }

    /**
     * @param array<ColumnInputEntity> $columns
     * @param string $fieldName
     *
     * @return array
     */
    private function undoColumn(array $columns, string $fieldName): array
    {
        return array_map(function(ColumnInputEntity $column) use($fieldName) {
            if ($column->name !== $fieldName) {
                return $column;
            }

            // Reset the column. Only the status needs to be updated.
            $column->changeIf();
            return $column;
        }, $columns);
    }

    /**
     * @param string $fieldName
     *
     * @return void
     */
    public function cancel(string $fieldName): void
    {
        $columns = $this->getTableColumns();
        if (!isset($columns[$fieldName])) {
            $table = $this->getTableName();
            $this->alert()
                ->title($this->trans->lang('Error'))
                ->error("Unable to find the field with '$fieldName' in table '$table'.");
            return;
        }

        $columns = $this->undoColumn($columns, $fieldName);
        $this->cl(Table::class)->show($this->metadata(), $columns);
    }
}
