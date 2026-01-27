<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Ddl\Column;

use Lagdo\DbAdmin\Db\UiData\Ddl\ColumnInputDto;

class UpdateFunc extends FuncComponent
{
    /**
     * @param string $columnId
     *
     * @return void
     */
    public function edit(string $columnId): void
    {
        $table = $this->getCurrentTable();
        $column = $this->getFieldColumn($columnId);
        if ($column === null) {
            $this->alert()
                ->title($this->trans->lang('Error'))
                ->error("Unable to find the requested column in table '$table'.");
            return;
        }

        $title = $column->added() ?
            "Edit new column in table $table" :
            "Edit column {$column->name} in table $table";
        $content = $this->columnUi
            ->metadata($this->metadata())
            ->column($column);
        $buttons = [[
            'title' => 'Cancel',
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ],[
            'title' => 'Save',
            'class' => 'btn btn-primary',
            'click' => $this->rq()->save($columnId, $this->columnUi->editFormValues()),
        ]];

        $this->modal()->show($title, $content, $buttons);
    }

    /**
     * @param string $columnId
     * @param array  $values
     *
     * @return void
     */
    public function save(string $columnId, array $values): void
    {
        $columns = $this->getTableColumns();
        if (!isset($columns[$columnId])) {
            $table = $this->getCurrentTable();
            $this->alert()
                ->title($this->trans->lang('Error'))
                ->error("Unable to find the requested column in table '$table'.");
            return;
        }

        $column = $columns[$columnId];
        $column->setValues($this->getUserFormValues($values));
        if ($column->changed() || $column->unchanged()) {
            $column->changeIf();
        }

        $this->modal()->hide();

        $this->cl(Wrapper::class)->show($this->metadata(), $columns);
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
            $table = $this->getCurrentTable();
            $this->alert()
                ->title($this->trans->lang('Error'))
                ->error("Unable to find the requested column in table '$table'.");
            return;
        }

        $fields = $this->metadata()['fields'];
        $column = $columns[$columnId];
        if (isset($fields[$column->name])) {
            // Reset the column with values from the database.
            $column = new ColumnInputDto($fields[$column->name]);
            $column->undo();
            $columns[$columnId] = $column;
        }

        $this->cl(Wrapper::class)->show($this->metadata(), $columns);
    }
}
