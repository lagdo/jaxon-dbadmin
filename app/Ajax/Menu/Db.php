<?php

namespace Lagdo\DbAdmin\App\Ajax\Menu;

use Jaxon\App\Component;
use Lagdo\DbAdmin\App\Ajax\Db\Database;
use Lagdo\DbAdmin\App\Ajax\Db\Server;
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
            'databases' => rq(Server::class)->showDatabases(),
            'privileges' => rq(Server::class)->showPrivileges(),
            'processes' => rq(Server::class)->showProcesses(),
            'variables' => rq(Server::class)->showVariables(),
            'status' => rq(Server::class)->showStatus(),
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
