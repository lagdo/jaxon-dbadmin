<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Ddl\Column;

use Lagdo\DbAdmin\Db\Page\Ddl\ColumnEntity;

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

        $title = $column->status === 'added' ?
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
        $column->setValues($this->getColumnValues($values));
        if ($column->status === 'edited' || $column->status === 'unchanged') {
            $column->status = $column->fieldEdited() ? 'edited' : 'unchanged';
        }

        $this->modal()->hide();

        $this->cl(Table::class)->show($this->metadata(), $columns);
    }

    /**
     * @param array<ColumnEntity> $columns
     * @param string $fieldName
     *
     * @return array
     */
    private function resetColumn(array $columns, string $fieldName): array
    {
        return array_map(function(ColumnEntity $column) use($fieldName) {
            if ($column->name !== $fieldName) {
                return $column;
            }

            // Reset the column with values from the database.
            $column = new ColumnEntity($this->metadata()['fields'][$fieldName]);
            $column->status = 'unchanged';
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

        $columns = $this->resetColumn($columns, $fieldName);
        $this->cl(Table::class)->show($this->metadata(), $columns);
    }
}
