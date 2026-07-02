<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Ddl\Column;

use Jaxon\Attributes\Attribute\Callback;

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

        $input = $this->db()->setInputFieldProperties($input);
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
            'title' => 'Edit',
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
    #[Callback('jaxon.dbadmin.bagTableForm')]
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
        $input->setValues($this->getColumnFormValues($values));
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
    #[Callback('jaxon.dbadmin.bagTableForm')]
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

        $input = $inputs[$columnId];
        // Reset the column input with values from the database.
        $tableDto = $this->tableDto();
        if (isset($tableDto->columns[$input->column->name])) {
            $input->reset();
        }

        $this->cl(Wrapper::class)->show($this->metadata(), $inputs);
    }
}
