<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Ddl\Column;

use Lagdo\DbAdmin\Db\Page\Ddl\ColumnInputEntity;

class UpdateFunc extends FuncComponent
{
    /**
     * The form id
     */
    protected $formId = 'dbadmin-table-column-update-form';

    /**
     * @param string $columnId
     *
     * @return void
     */
    public function edit(string $columnId): void
    {
        $table = $this->getTableName();
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
            ->formId($this->formId)
            ->column($column);
        $buttons = [[
            'title' => 'Cancel',
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ],[
            'title' => 'Save',
            'class' => 'btn btn-primary',
            'click' => $this->rq()->save($columnId, je($this->formId)->rd()->form()),
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
            $table = $this->getTableName();
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

        $this->cl(Table::class)->show($this->metadata(), $columns);
    }

    /**
     * @param array<ColumnInputEntity> $columns
     * @param string $columnId
     *
     * @return array
     */
    private function undoColumn(array $columns, string $columnId): array
    {
        $fields = $this->metadata()['fields'];
        foreach ($columns as $_columnId => &$column) {
            if ($_columnId === $columnId && isset($fields[$column->name])) {
                // Reset the column with values from the database.
                $column = new ColumnInputEntity($fields[$column->name]);
                $column->undo();
                break;
            }
        }
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

        $columns = $this->undoColumn($columns, $columnId);
        $this->cl(Table::class)->show($this->metadata(), $columns);
    }
}
