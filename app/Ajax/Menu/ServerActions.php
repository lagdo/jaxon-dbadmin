<?php

namespace Lagdo\DbAdmin\App\Ajax\Menu;

use Jaxon\App\Component;
use Lagdo\DbAdmin\App\Ajax\Db\Command;
use Lagdo\DbAdmin\App\Ajax\Db\Export;
use Lagdo\DbAdmin\App\Ajax\Db\Import;
use Lagdo\DbAdmin\Ui\MenuBuilder;

use function Jaxon\rq;

class ServerActions extends Component
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
        return $this->ui->menuCommands($this->actions);
    }

    /**
     * @exclude
     *
     * @param array $actions
     *
     * @return void
     */
    public function update(array $actions)
    {
        $handlers = [
            'server-command' => rq(Command::class)->showServerForm(),
            'server-import' => rq(Import::class)->showServerForm(),
            'server-export' => rq(Export::class)->showServerForm(),
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
