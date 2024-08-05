<?php

namespace Lagdo\DbAdmin\App\Ajax\Menu;

use Lagdo\DbAdmin\App\Ajax\Db\Database;
use Lagdo\DbAdmin\App\Ajax\Db\Server\Databases;
use Lagdo\DbAdmin\App\Ajax\Db\Server\Privileges;
use Lagdo\DbAdmin\App\Ajax\Db\Server\Processes;
use Lagdo\DbAdmin\App\Ajax\Db\Server\Status;
use Lagdo\DbAdmin\App\Ajax\Db\Server\Variables;
use Lagdo\DbAdmin\App\MenuComponent;

use function Jaxon\rq;

class Db extends MenuComponent
{
    /**
     * @var array
     */
    private $actions = [];

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->ui->menuActions($this->actions);
    }

    /**
     * @exclude
     *
     * @return void
     */
    public function showServer()
    {
        // Content from the connect_error() function in connect.inc.php
        $this->actions = [
            'databases' => [
                'title' => $this->trans->lang('Databases'),
                'handler' => rq(Databases::class)->update(),
            ],
        ];
        // if($this->driver->support('database'))
        // {
        //     $this->actions['databases'] = [
        //         'title' => $this->trans->lang('Databases'),
        //         'handler' => rq(Databases::class)->update(),
        //     ];
        // }
        if ($this->driver->support('privileges')) {
            $this->actions['privileges'] = [
                'title' => $this->trans->lang('Privileges'),
                'handler' => rq(Privileges::class)->update(),
            ];
        }
        if ($this->driver->support('processlist')) {
            $this->actions['processes'] = [
                'title' => $this->trans->lang('Process list'),
                'handler' => rq(Processes::class)->update(),
            ];
        }
        if ($this->driver->support('variables')) {
            $this->actions['variables'] = [
                'title' => $this->trans->lang('Variables'),
                'handler' => rq(Variables::class)->update(),
            ];
        }
        if ($this->driver->support('status')) {
            $this->actions['status'] = [
                'title' => $this->trans->lang('Status'),
                'handler' => rq(Status::class)->update(),
            ];
        }

        $this->render();
    }

    /**
     * @exclude
     *
     * @return void
     */
    public function showDatabase()
    {
        $this->actions = [
            'table' => [
                'title' => $this->trans->lang('Tables'),
                'handler' => rq(Database::class)->showTables(),
            ],
        ];
        if ($this->driver->support('view')) {
            $this->actions['view'] = [
                'title' => $this->trans->lang('Views'),
                'handler' => rq(Database::class)->showViews(),
            ];
        }
        // Todo: Implement features and enable menu items.
        // if ($this->driver->support('routine')) {
        //     $this->actions['routine'] = [
        //         'title' => $this->trans->lang('Routines'),
        //         'handler' => rq(Database::class)->showRoutines(),
        //     ];
        // }
        // if ($this->driver->support('sequence')) {
        //     $this->actions['sequence'] = [
        //         'title' => $this->trans->lang('Sequences'),
        //         'handler' => rq(Database::class)->showSequences(),
        //     ];
        // }
        // if ($this->driver->support('type')) {
        //     $this->actions['type'] = [
        //         'title' => $this->trans->lang('User types'),
        //         'handler' => rq(Database::class)->showUserTypes(),
        //     ];
        // }
        // if ($this->driver->support('event')) {
        //     $this->actions['event'] = [
        //         'title' => $this->trans->lang('Events'),
        //         'handler' => rq(Database::class)->showEvents(),
        //     ];
        // }

        $this->render();
    }
}
