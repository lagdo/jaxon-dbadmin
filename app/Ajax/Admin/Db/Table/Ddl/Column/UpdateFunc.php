<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Ddl\Column;

use Lagdo\DbAdmin\Support\Driver\UiDto\Ddl\DdInputDto;

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
        $input = $this->getColumnInput($columnId);
        if ($input === null) {
            $this->alert()
                ->title($this->trans->lang('Error'))
                ->error("Unable to find the requested column in table '$table'.");
            return;
        }

        $primaryColumn = $this->getTableBag('primary', '');

        $title = $input->added() ?
            "Edit new column in table $table" :
            "Edit column {$input->column->name} in table $table";
        $content = $this->columnUi
            ->metadata($this->metadata())
            ->column($input, $primaryColumn);
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
        $inputs = $this->getColumnInputs();
        if (!isset($inputs[$columnId])) {
            $table = $this->getCurrentTable();
            $this->alert()
                ->title($this->trans->lang('Error'))
                ->error("Unable to find the requested column in table '$table'.");
            return;
        }

        $input = $inputs[$columnId];
        $input->setValues($this->getUserFormValues($values));
        if ($input->changed() || $input->unchanged()) {
            $input->changeIf();
        }

        $this->modal()->hide();

        $this->cl(Wrapper::class)->show($this->metadata(), $inputs);
    }

    /**
     * @param string $columnId
     *
     * @return void
     */
    public function cancel(string $columnId): void
    {
        $inputs = $this->getColumnInputs();
        if (!isset($inputs[$columnId])) {
            $table = $this->getCurrentTable();
            $this->alert()
                ->title($this->trans->lang('Error'))
                ->error("Unable to find the requested column in table '$table'.");
            return;
        }

        $columns = $this->metadata()['columns'];
        $input = $inputs[$columnId];
        $columnName = $input->column->name;

        // Reset the column input with values from the database.
        if (isset($columns[$columnName])) {
            $input = new DdInputDto($columns[$columnName]);
            $input->undo();
            $inputs[$columnId] = $input;
        }

        $this->cl(Wrapper::class)->show($this->metadata(), $inputs);
    }
}
