<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Ddl\Column;

use Lagdo\DbAdmin\Db\Page\Ddl\ColumnEntity;

use function array_filter;
use function array_map;

class DeleteFunc extends FuncComponent
{
    /**
     * @param array $columns
     * @param string $fieldName
     *
     * @return array
     */
    private function updateColumns(array $columns, string $fieldName): array
    {
        $column = $columns[$fieldName];
        if ($column->status !== 'added') {
            // An existing column is set to be deleted.
            $column->status = 'deleted';
            return $columns;
        }

        // Remove the column.
        $columns = array_filter($columns, fn($c) => $c->name !== $column->name);
        // Reset the columns positions.
        $position = 0;
        foreach ($columns as $column) {
            if ($column->status === 'added') {
                $column->name = $this->addedColumnName($position);
            }
            $column->position = $position++;
        }
        return $columns;
    }

    /**
     * @param ColumnEntity $column
     * @param string $fieldName
     *
     * @return ColumnEntity
     */
    private function resetColumn(ColumnEntity $column, string $fieldName): ColumnEntity
    {
        if ($column->name !== $fieldName) {
            return $column;
        }

        // Reset the column.
        $new = $this->getFieldColumn($fieldName);
        $new->status = 'unchanged';
        $new->position = $column->position;
        return $new;
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

        $this->stash()->set('table.metadata', $this->metadata());

        $columns = $this->updateColumns($columns, $fieldName);
        $this->stash()->set('table.columns', $columns);

        $this->cl(Table::class)->render();
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

        $this->stash()->set('table.metadata', $this->metadata());

        $columns = array_map(fn($c) => $this->resetColumn($c, $fieldName), $columns);
        $this->stash()->set('table.columns', $columns);

        $this->cl(Table::class)->render();
    }
}
