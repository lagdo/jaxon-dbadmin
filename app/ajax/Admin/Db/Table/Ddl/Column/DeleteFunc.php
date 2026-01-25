<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Ddl\Column;

use Lagdo\DbAdmin\Db\UiData\Ddl\ColumnInputDto;

class DeleteFunc extends FuncComponent
{
    /**
     * @param string $columnId
     * @param array<ColumnInputDto> $columns
     *
     * @return array
     */
    private function updateColumns(string $columnId, array $columns): array
    {
        $column = $columns[$columnId];
        if ($column->added()) {
            // Remove the column.
            $columns[$columnId] = null;
            return $columns;
        }

        // An existing column is set to be dropped.
        $column->drop();
        return $columns;
    }

    /**
     * @param string $columnId
     *
     * @return void
     */
    public function exec(string $columnId): void
    {
        $columns = $this->getTableColumns();
        if (!isset($columns[$columnId])) {
            $table = $this->getTableName();
            $this->alert()
                ->title($this->trans->lang('Error'))
                ->error("Unable to find the requested column in table '$table'.");
            return;
        }

        $columns = $this->updateColumns($columnId, $columns);
        $this->cl(Wrapper::class)->show($this->metadata(), $columns);
    }

    /**
     * @param string $columnId
     * @param array<ColumnInputDto> $columns
     *
     * @return array
     */
    private function undoColumn(string $columnId, array $columns): array
    {
        // Reset the column. Only the status needs to be updated.
        $columns[$columnId]->changeIf();
        return $columns;
    }

    /**
     * @param string $columnId
     *
     * @return void
     */
    public function cancel(string $columnId): void
    {
        $columns = $this->getTableColumns();
        if (!isset($columns[$columnId])) {
            $table = $this->getTableName();
            $this->alert()
                ->title($this->trans->lang('Error'))
                ->error("Unable to find the requested column in table '$table'.");
            return;
        }

        $columns = $this->undoColumn($columnId, $columns);
        $this->cl(Wrapper::class)->show($this->metadata(), $columns);
    }
}
