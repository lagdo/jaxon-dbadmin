<?php

namespace Lagdo\DbAdmin\App\Ajax\Menu;

use Lagdo\DbAdmin\App\Ajax\Db\Database\Events;
use Lagdo\DbAdmin\App\Ajax\Db\Database\Routines;
use Lagdo\DbAdmin\App\Ajax\Db\Database\Sequences;
use Lagdo\DbAdmin\App\Ajax\Db\Database\Tables;
use Lagdo\DbAdmin\App\Ajax\Db\Database\UserTypes;
use Lagdo\DbAdmin\App\Ajax\Db\Database\Views;
use Lagdo\DbAdmin\App\Ajax\Db\Server\Databases;
use Lagdo\DbAdmin\App\Ajax\Db\Server\Privileges;
use Lagdo\DbAdmin\App\Ajax\Db\Server\Processes;
use Lagdo\DbAdmin\App\Ajax\Db\Server\Status;
use Lagdo\DbAdmin\App\Ajax\Db\Server\Variables;
use Lagdo\DbAdmin\App\MenuComponent;

use function Jaxon\rq;

class Sections extends MenuComponent
{
    /**
     * @var array
     */
    private $actions = [];

    /**
     * @var string
     */
    private $activeItem = '';

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->ui->menuActions($this->actions, $this->activeItem);
    }

    /**
     * @exclude
     *
     * @param string $activeItem
     *
     * @return void
     */
    public function server(string $activeItem = '')
    {
        $this->activeItem = $activeItem;
        // Content from the connect_error() function in connect.inc.php
        $this->actions = [
            'databases' => [
                'title' => $this->trans->lang('Databases'),
                'handler' => rq(Databases::class)->refresh(),
            ],
        ];
        // if($this->db->support('database'))
        // {
        //     $this->actions['databases'] = [
        //         'title' => $this->trans->lang('Databases'),
        //         'handler' => rq(Databases::class)->refresh(),
        //     ];
        // }
        if ($this->db->support('privileges')) {
            $this->actions['privileges'] = [
                'title' => $this->trans->lang('Privileges'),
                'handler' => rq(Privileges::class)->refresh(),
            ];
        }
        if ($this->db->support('processlist')) {
            $this->actions['processes'] = [
                'title' => $this->trans->lang('Process list'),
                'handler' => rq(Processes::class)->refresh(),
            ];
        }
        if ($this->db->support('variables')) {
            $this->actions['variables'] = [
                'title' => $this->trans->lang('Variables'),
                'handler' => rq(Variables::class)->refresh(),
            ];
        }
        if ($this->db->support('status')) {
            $this->actions['status'] = [
                'title' => $this->trans->lang('Status'),
                'handler' => rq(Status::class)->refresh(),
            ];
        }

        $this->render();
    }

    /**
     * @exclude
     *
     * @param string $activeItem
     *
     * @return void
     */
    public function database(string $activeItem = '')
    {
        $this->activeItem = $activeItem;
        $this->actions = [
            'tables' => [
                'title' => $this->trans->lang('Tables'),
                'handler' => rq(Tables::class)->refresh(),
            ],
        ];
        if ($this->db->support('view')) {
            $this->actions['views'] = [
                'title' => $this->trans->lang('Views'),
                'handler' => rq(Views::class)->refresh(),
            ];
        }
        // Todo: Implement features and enable menu items.
        // if ($this->db->support('routine')) {
        //     $this->actions['routines'] = [
        //         'title' => $this->trans->lang('Routines'),
        //         'handler' => rq(Routines::class)->refresh(),
        //     ];
        // }
        // if ($this->db->support('sequence')) {
        //     $this->actions['sequences'] = [
        //         'title' => $this->trans->lang('Sequences'),
        //         'handler' => rq(Sequences::class)->refresh(),
        //     ];
        // }
        // if ($this->db->support('type')) {
        //     $this->actions['types'] = [
        //         'title' => $this->trans->lang('User types'),
        //         'handler' => rq(UserTypes::class)->refresh(),
        //     ];
        // }
        // if ($this->db->support('event')) {
        //     $this->actions['events'] = [
        //         'title' => $this->trans->lang('Events'),
        //         'handler' => rq(Events::class)->refresh(),
        //     ];
        // }

        $this->render();
    }
}
