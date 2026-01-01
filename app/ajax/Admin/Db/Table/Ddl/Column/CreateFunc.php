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
        $title = 'New column in table ' . $this->getTableName();
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
        $columns = $this->getTableColumns();
        // Reset the columns positions.
        $position = 0;
        foreach ($columns as $column) {
            if ($column->status === 'added') {
                $column->name = $this->addedColumnName($position);
            }
            $column->position = $position++;
        }

        // Create an empty field and fill with the form data.
        $column = $this->getEmptyColumn();
        $column->name = $this->addedColumnName($position);
        $column->status = 'added';
        $column->position = $position;
        $column->updateField($this->getColumnValues($values));
        // Append the new colum to the list, indexed by its name.
        $columns[$column->name] = $column;

        $this->modal()->hide();

        $this->stash()->set('table.metadata', $this->metadata());
        $this->stash()->set('table.columns', $columns);

        $this->cl(Table::class)->render();
    }
}
