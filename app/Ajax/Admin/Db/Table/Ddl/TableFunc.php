<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Ddl;

use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Database\Tables;

/**
 * Create, alter or drop a table
 */
#[Databag('dbadmin.table')]
class TableFunc extends Column\FuncComponent
{
    /**
     * Create a new table
     *
     * @param array  $tableInputs      The table values
     *
     * @return void
     */
    public function create(array $tableInputs): void
    {
        $this->setCurrentTable('');

        $tableDto = $this->tableDto();
        $tableDto->setValues($this->getTableFormValues($tableInputs));
        $tableDto->columns = $this->getColumnInputs();

        $result = $this->driver()->createTable($tableDto);
        if($result->error !== null)
        {
            $this->alert()->error($result->error);
            return;
        }

        $this->cl(Table::class)->show($tableDto->values()->name);
        $this->showBreadcrumbs();

        $this->alert()
            ->title($this->trans->lang('Success'))
            ->success($result->message);
    }

    /**
     * @param array  $tableInputs      The table values
     *
     * @return void
     */
    public function alter(array $tableInputs): void
    {
        $tableName = $this->getCurrentTable();
        $tableDto = $this->tableDto();
        if ($tableDto->status->name === '') {
            $this->alert()
                ->title('Error')
                ->error("Unable to find the '$tableName' table.");
            return;
        }

        $tableDto->setValues($this->getTableFormValues($tableInputs));
        $tableDto->columns = $this->getColumnInputs();

        $result = $this->driver()->alterTable($tableDto);
        if($result->error !== null)
        {
            $this->alert()->error($result->error);
            return;
        }

        $this->cl(Alter::class)->render();

        $this->alert()
            ->title($this->trans->lang('Success'))
            ->success($result->message);
    }

    /**
     * @param string $table
     *
     * @return void
     */
    public function drop(string $table): void
    {
        $result = $this->driver()->dropTable($table);
        if ($result->error !== null) {
            $this->alert()
                ->title($this->trans->lang('Error'))
                ->error($result->error);
            return;
        }

        $this->cl(Tables::class)->show();
        $this->showBreadcrumbs();

        $this->alert()
            ->title($this->trans->lang('Success'))
            ->success($result->message);
    }
}
