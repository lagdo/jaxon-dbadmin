<?php

namespace Lagdo\DbAdmin\App\Ajax\Menu;

use Jaxon\App\Component;
use Lagdo\DbAdmin\App\Ajax\Db\Database;
use Lagdo\DbAdmin\App\Ajax\Db\Server\Databases;
use Lagdo\DbAdmin\App\Ajax\Db\Server\Privileges;
use Lagdo\DbAdmin\App\Ajax\Db\Server\Processes;
use Lagdo\DbAdmin\App\Ajax\Db\Server\Status;
use Lagdo\DbAdmin\App\Ajax\Db\Server\Variables;
use Lagdo\DbAdmin\Ui\MenuBuilder;

use function Jaxon\rq;

class Db extends Component
{
    /**
     * @var array
     */
    private $actions = [];

    /**
     * @param MenuBuilder $ui
     */
    public function __construct(private MenuBuilder $ui)
    {}

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
     * @param array $actions
     *
     * @return void
     */
    public function showServer(array $actions)
    {
        $handlers = [
            'databases' => rq(Databases::class)->update(),
            'privileges' => rq(Privileges::class)->update(),
            'processes' => rq(Processes::class)->update(),
            'variables' => rq(Variables::class)->update(),
            'status' => rq(Status::class)->update(),
        ];

        $this->actions = [];
        foreach($actions as $id => $title)
        {
            if (isset($handlers[$id])) {
                $this->actions = [$title, $handlers[$id]];
            }
        }
        $this->refresh();
    }

    /**
     * @exclude
     *
     * @param array $actions
     *
     * @return void
     */
    public function showDatabase(array $actions)
    {
        $handlers = [
            'table' => rq(Database::class)->showTables(),
            'view' => rq(Database::class)->showViews(),
            'routine' => rq(Database::class)->showRoutines(),
            'sequence' => rq(Database::class)->showSequences(),
            'type' => rq(Database::class)->showUserTypes(),
            'event' => rq(Database::class)->showEvents(),
        ];

        $this->actions = [];
        foreach($actions as $id => $title)
        {
            if (isset($handlers[$id])) {
                $this->actions = [$title, $handlers[$id]];
            }
        }
        $this->refresh();
    }
}
