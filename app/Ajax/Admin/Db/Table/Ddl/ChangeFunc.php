<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Ddl;

/**
 * Show the changes on a table.
 */
class ChangeFunc extends Column\FuncComponent
{
    /**
     * @param array $tableInputs
     *
     * @return void
     */
    public function create(array $tableInputs): void
    {
        $createDto = $this->db()->getCreateTableDto($tableInputs);
        $columns = $this->getColumnInputs();

        $title = 'Values for the new table';
        $content = $this->columnUi
            ->metadata($this->metadata())
            ->createValues($createDto, $columns);
        $buttons = [[
            'title' => 'Close',
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ]];
        $this->modal()->show($title, $content ?: '&nbsp;', $buttons);
    }
    /**
     * @param array $tableInputs
     *
     * @return void
     */
    public function alter(array $tableInputs): void
    {
        $tableName = $this->getCurrentTable();
        $tableDto = $this->metadata()['table'];
        if ($tableDto === null) {
            $this->alert()->title('Error')->error("Unable to find the '$tableName' table.");
            return;
        }

        $alterDto = $this->db()->getAlterTableDto($tableInputs);
        $columns = $this->getColumnInputs();

        $title = "Changes in the table $tableName";
        $content = $this->columnUi
            ->metadata($this->metadata())
            ->alterValues($tableDto, $alterDto, $columns);
        $buttons = [[
            'title' => 'Close',
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ]];
        $this->modal()->show($title, $content ?: '&nbsp;', $buttons);
    }
}
