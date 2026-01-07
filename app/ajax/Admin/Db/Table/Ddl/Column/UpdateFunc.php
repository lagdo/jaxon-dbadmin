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
     * @param string $fieldName
     *
     * @return void
     */
    public function edit(string $fieldName): void
    {
        $table = $this->getTableName();
        $column = $this->getFieldColumn($fieldName);
        if ($column === null) {
            $this->alert()
                ->title($this->trans->lang('Error'))
                ->error("Unable to find the field with '$fieldName' in table '$table'.");
            return;
        }

        $title = $column->added() ?
            "Edit new column in table $table" :
            "Edit column $fieldName in table $table";
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
            'click' => $this->rq()->save($fieldName, je($this->formId)->rd()->form()),
        ]];

        $this->modal()->show($title, $content, $buttons);
    }

    /**
     * @param string $fieldName
     * @param array  $values
     *
     * @return void
     */
    public function save(string $fieldName, array $values): void
    {
        $columns = $this->getTableColumns();
        if (!isset($columns[$fieldName])) {
            $table = $this->getTableName();
            $this->alert()
                ->title($this->trans->lang('Error'))
                ->error("Unable to find the field with '$fieldName' in table '$table'.");
            return;
        }

        $column = $columns[$fieldName];
        $column->setValues($this->getUserFormValues($values));
        if ($column->changed() || $column->unchanged()) {
            $column->changeIf();
        }

        $this->modal()->hide();

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

            // Reset the column with values from the database.
            $column = new ColumnInputEntity($this->metadata()['fields'][$fieldName]);
            $column->undo();
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
