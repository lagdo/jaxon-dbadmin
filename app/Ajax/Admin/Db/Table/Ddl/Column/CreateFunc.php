<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Ddl\Column;

use Jaxon\Attributes\Attribute\Callback;

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
        $input = $this->newColumnInput();

        $tableName = $this->getCurrentTable();
        $title = $tableName === '' ? 'New column' : "New column in table $tableName";
        $content = $this->columnUi
            ->metadata($this->metadata())
            ->column($input);
        $buttons = [[
            'title' => 'Cancel',
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ],[
            'title' => 'Add',
            'class' => 'btn btn-primary',
            'click' => $this->rq()->save($columnId, $this->columnUi->editFormValues()),
        ]];

        $this->modal()->show($title, $content, $buttons);

        $this->response()->jq('.dbadmin-column-edit-type',
            '#' . $this->columnUi->editFormId())->trigger('change');
        $this->response()->jq('.dbadmin-column-foreign-key',
            '#' . $this->columnUi->editFormId())->trigger('change');
    }

    /**
     * Insert a new column after a given column
     *
     * @param string $columnId
     * @param array  $values
     *
     * @return void
     */
    #[Callback('jaxon.dbadmin.bagTableForm')]
    public function save(string $columnId, array $values): void
    {
        // Create an empty input and fill with the form data.
        $input = $this->newColumnInput($this->getColumnFormValues($values));
        $input->add();

        $this->modal()->hide();

        $this->cl(Wrapper::class)->show($this->metadata(), [
            ...$this->getColumnInputs(),
            $input,
        ]);
    }
}
