<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Ddl;

use Jaxon\Attributes\Attribute\Before;
use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Database\Tables;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\FuncComponent;

/**
 * Create, alter or drop a table
 */
#[Databag('dbadmin.table')]
class TableFunc extends FuncComponent
{
    /**
     * Create a new table
     *
     * @param array  $tableInputs      The table values
     *
     * @return void
     */
    #[Before('notYetAvailable')]
    public function create(array $tableInputs): void
    {
        // $columns = $this->getTableBag('columns');
        // $tableInputs = array_merge($this->defaults, $tableInputs);

        // $result = $this->db()->createTable($tableInputs);
        // if(!$result['success'])
        // {
        //     $this->alert()->error($result['error']);
        //     return;
        // }

        // $this->show($tableInputs['name']);
        // $this->alert()->success($result['message']);
    }

    /**
     * @param array  $tableInputs      The table values
     *
     * @return void
     */
    #[Before('notYetAvailable')]
    public function alter(array $tableInputs): void
    {
        // $table = $this->getCurrentTable();
        // $tableInputs = array_merge($this->defaults, $tableInputs);

        // $result = $this->db()->alterTable($table, $tableInputs);
        // if(!$result['success'])
        // {
        //     $this->alert()->error($result['error']);
        //     return;
        // }

        // $this->cl(Table::class)->render();
        // $this->alert()->success($result['message']);
    }

    /**
     * @param string $table
     *
     * @return void
     */
    public function drop(string $table): void
    {
        $result = $this->db()->dropTable($table);
        if (isset($result['error'])) {
            $this->alert()
                ->title($this->trans->lang('Error'))
                ->error($result['error']);
            return;
        }

        $this->cl(Tables::class)->show();
        $this->showBreadcrumbs();

        $this->alert()
            ->title($this->trans->lang('Success'))
            ->success($result['message']);
    }
}
