<?php

namespace Lagdo\DbAdmin\App\Ajax\Menu;

use Lagdo\DbAdmin\App\Ajax\Db\Command;
use Lagdo\DbAdmin\App\Ajax\Db\Export;
use Lagdo\DbAdmin\App\Ajax\Db\Import;
use Lagdo\DbAdmin\App\MenuComponent;

use function Jaxon\rq;

class ServerActions extends MenuComponent
{
    /**
     * @inheritDoc
     */
    public function html(): string
    {
        $actions = [
            'server-command' => [
                'title' => $this->trans->lang('Query'),
                'handler' => rq(Command::class)->showServerForm(),
            ],
            'server-import' => [
                'title' => $this->trans->lang('Import'),
                'handler' => rq(Import::class)->showServerForm(),
            ],
            'server-export' => [
                'title' => $this->trans->lang('Export'),
                'handler' => rq(Export::class)->showServerForm(),
            ],
        ];

        return $this->ui->menuCommands($actions);
    }
}
