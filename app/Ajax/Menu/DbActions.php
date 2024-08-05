<?php

namespace Lagdo\DbAdmin\App\Ajax\Menu;

use Lagdo\DbAdmin\App\Ajax\Db\Command;
use Lagdo\DbAdmin\App\Ajax\Db\Export;
use Lagdo\DbAdmin\App\Ajax\Db\Import;
use Lagdo\DbAdmin\App\MenuComponent;

use function Jaxon\rq;

class DbActions extends MenuComponent
{
    /**
     * @inheritDoc
     */
    public function html(): string
    {
        $actions = [
            'database-command' => [
                'title' => $this->trans->lang('Query'),
                'handler' => rq(Command::class)->showDatabaseForm(),
            ],
            'database-import' => [
                'title' => $this->trans->lang('Import'),
                'handler' => rq(Import::class)->showDatabaseForm(),
            ],
            'database-export' => [
                'title' => $this->trans->lang('Export'),
                'handler' => rq(Export::class)->showDatabaseForm(),
            ],
        ];

        return $this->ui->menuCommands($actions);
    }
}
