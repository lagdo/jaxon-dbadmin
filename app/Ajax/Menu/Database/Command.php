<?php

namespace Lagdo\DbAdmin\App\Ajax\Menu\Database;

use Lagdo\DbAdmin\App\Ajax\Db\Database\Export;
use Lagdo\DbAdmin\App\Ajax\Db\Database\Import;
use Lagdo\DbAdmin\App\Ajax\Db\Database\Query;
use Lagdo\DbAdmin\App\MenuComponent;

use function Jaxon\rq;

class Command extends MenuComponent
{
    /**
     * @var string
     */
    private $activeItem = '';

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        $actions = [
            'database-query' => [
                'title' => $this->trans()->lang('Query'),
                'handler' => rq(Query::class)->database(),
            ],
            'database-import' => [
                'title' => $this->trans()->lang('Import'),
                'handler' => rq(Import::class)->database(),
            ],
            'database-export' => [
                'title' => $this->trans()->lang('Export'),
                'handler' => rq(Export::class)->database(),
            ],
        ];

        return $this->ui()->menuCommands($actions, $this->activeItem);
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
        $this->render();
    }
}
