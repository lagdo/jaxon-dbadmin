<?php

namespace Lagdo\DbAdmin\Ajax\App\Menu;

use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\Ajax\App\Db\Database\Events;
use Lagdo\DbAdmin\Ajax\App\Db\Database\Routines;
use Lagdo\DbAdmin\Ajax\App\Db\Database\Sequences;
use Lagdo\DbAdmin\Ajax\App\Db\Database\Tables;
use Lagdo\DbAdmin\Ajax\App\Db\Database\UserTypes;
use Lagdo\DbAdmin\Ajax\App\Db\Database\Views;
use Lagdo\DbAdmin\Ajax\App\Db\Server\Databases;
use Lagdo\DbAdmin\Ajax\App\Db\Server\Privileges;
use Lagdo\DbAdmin\Ajax\App\Db\Server\Processes;
use Lagdo\DbAdmin\Ajax\App\Db\Server\Status;
use Lagdo\DbAdmin\Ajax\App\Db\Server\Variables;
use Lagdo\DbAdmin\Ajax\MenuComponent;

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
        return $this->ui()->actions($this->actions, $this->activeItem);
    }

    /**
     * @param string $activeItem
     *
     * @return void
     */
    #[Exclude]
    public function server(string $activeItem = ''): void
    {
        $this->activeItem = $activeItem;
        // Content from the connect_error() function in connect.inc.php
        // if($this->db()->support('database'))
        {
            $this->actions['databases'] = [
                'title' => $this->trans()->lang('Databases'),
                'handler' => rq(Databases::class)->show(),
            ];
        }
        if ($this->db()->support('privileges')) {
            $this->actions['privileges'] = [
                'title' => $this->trans()->lang('Privileges'),
                'handler' => rq(Privileges::class)->show(),
            ];
        }
        if ($this->db()->support('processlist')) {
            $this->actions['processes'] = [
                'title' => $this->trans()->lang('Process list'),
                'handler' => rq(Processes::class)->show(),
            ];
        }
        if ($this->db()->support('variables')) {
            $this->actions['variables'] = [
                'title' => $this->trans()->lang('Variables'),
                'handler' => rq(Variables::class)->show(),
            ];
        }
        if ($this->db()->support('status')) {
            $this->actions['status'] = [
                'title' => $this->trans()->lang('Status'),
                'handler' => rq(Status::class)->show(),
            ];
        }

        $this->render();
    }

    /**
     * @param string $activeItem
     *
     * @return void
     */
    #[Exclude]
    public function database(string $activeItem = ''): void
    {
        $this->activeItem = $activeItem;
        $this->actions = [
            'tables' => [
                'title' => $this->trans()->lang('Tables'),
                'handler' => rq(Tables::class)->show(),
            ],
        ];
        if ($this->db()->support('view')) {
            $this->actions['views'] = [
                'title' => $this->trans()->lang('Views'),
                'handler' => rq(Views::class)->show(),
            ];
        }
        // Todo: Implement features and enable menu items.
        // if ($this->db()->support('routine')) {
        //     $this->actions['routines'] = [
        //         'title' => $this->trans()->lang('Routines'),
        //         'handler' => rq(Routines::class)->show(),
        //     ];
        // }
        // if ($this->db()->support('sequence')) {
        //     $this->actions['sequences'] = [
        //         'title' => $this->trans()->lang('Sequences'),
        //         'handler' => rq(Sequences::class)->show(),
        //     ];
        // }
        // if ($this->db()->support('type')) {
        //     $this->actions['types'] = [
        //         'title' => $this->trans()->lang('User types'),
        //         'handler' => rq(UserTypes::class)->show(),
        //     ];
        // }
        // if ($this->db()->support('event')) {
        //     $this->actions['events'] = [
        //         'title' => $this->trans()->lang('Events'),
        //         'handler' => rq(Events::class)->show(),
        //     ];
        // }

        $this->render();
    }
}
