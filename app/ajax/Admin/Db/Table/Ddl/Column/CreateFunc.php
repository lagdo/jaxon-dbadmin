<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Ddl\Column;

class CreateFunc extends FuncComponent
{
    /**
     * Insert a new column after a given column
     *
     * @param string $columnId
     *
     * @return void
     */
    public function add(string $columnId = ''): void
    {
        $tableName = $this->getTableName();
        $title = $tableName === '' ? 'New column' : "New column in table $tableName";
        $content = $this->columnUi
            ->metadata($this->metadata())
            ->column($this->getEmptyColumn());
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
     * Insert a new column after a given column
     *
     * @param string $columnId
     * @param array  $values
     *
     * @return void
     */
    public function save(string $columnId, array $values): void
    {
        // Create an empty field and fill with the form data.
        $column = $this->getEmptyColumn();
        $column->add();
        $column->setValues($this->getUserFormValues($values));

        $this->modal()->hide();

        $this->cl(Wrapper::class)->show($this->metadata(), [
            ...$this->getTableColumns(),
            $column,
        ]);
    }
}
