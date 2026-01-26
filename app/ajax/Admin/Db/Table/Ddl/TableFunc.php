<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Ddl;

use Jaxon\Attributes\Attribute\Before;
use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\Ajax\Admin\Db\Table\FuncComponent;

/**
 * Create, alter or drop a table
 */
#[Databag('dbadmin.table')]
#[Before('notYetAvailable')]
class TableFunc extends FuncComponent
{
    /**
     * Create a new table
     *
     * @param array  $values      The table values
     *
     * @return void
     */
    public function create(array $values): void
    {
        // $fields = $this->getTableBag('fields');
        // $values = array_merge($this->defaults, $values);

        // $result = $this->db()->createTable($values);
        // if(!$result['success'])
        // {
        //     $this->alert()->error($result['error']);
        //     return;
        // }

        // $this->show($values['name']);
        // $this->alert()->success($result['message']);
    }

    /**
     * @param string $table      The table name
     * @param array  $values      The table values
     *
     * @return void
     */
    public function alter(string $table, array $values): void
    {
        // $table = $this->getCurrentTable();
        // $values = array_merge($this->defaults, $values);

        // $result = $this->db()->alterTable($table, $values);
        // if(!$result['success'])
        // {
        //     $this->alert()->error($result['error']);
        //     return;
        // }

        // $this->cl(Table::class)->render();
        // $this->alert()->success($result['message']);
    }

    /**
     * @param string $table      The table name
     *
     * @return void
     */
    public function drop(string $table): void
    {
    }
}
