<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Ddl\Column;

use function Jaxon\je;

class CreateFunc extends FuncComponent
{
    /**
     * The form id
     */
    protected $formId = 'dbadmin-table-column-create-form';

    /**
     * Insert a new column at a given position
     *
     * @param int    $position      The new column is added before this position. Set to -1 to add at the end.
     *
     * @return void
     */
    public function add(int $position = -1): void
    {
        $tableName = $this->getTableName();
        $title = $tableName === '' ? 'New column' : "New column in table $tableName";
        $content = $this->columnUi
            ->metadata($this->metadata())
            ->formId($this->formId)
            ->column($this->getEmptyColumn());
        $buttons = [[
            'title' => 'Cancel',
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ],[
            'title' => 'Save',
            'class' => 'btn btn-primary',
            'click' => $this->rq()->save(je($this->formId)->rd()->form(), $position),
        ]];

        $this->modal()->show($title, $content, $buttons);
    }

    /**
     * Update the column at a given position
     *
     * @param array  $values
     * @param int    $position
     *
     * @return void
     */
    public function save(array $values, int $position): void
    {
        // Create an empty field and fill with the form data.
        $column = $this->getEmptyColumn();
        $column->status = 'added';
        $column->setValues($this->getUserFormValues($values));

        $this->modal()->hide();

        $this->cl(Table::class)->show($this->metadata(), [
            ...$this->getTableColumns(),
            $column,
        ]);
    }
}
