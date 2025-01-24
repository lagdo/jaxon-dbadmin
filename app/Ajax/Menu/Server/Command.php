<?php

namespace Lagdo\DbAdmin\App\Ajax\Menu\Server;

use Lagdo\DbAdmin\App\Ajax\Db\Server\Export;
use Lagdo\DbAdmin\App\Ajax\Db\Server\Import;
use Lagdo\DbAdmin\App\Ajax\Db\Server\Query;
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
            'server-query' => [
                'title' => $this->trans()->lang('Query'),
                'handler' => rq(Query::class)->server(),
            ],
            'server-import' => [
                'title' => $this->trans()->lang('Import'),
                'handler' => rq(Import::class)->server(),
            ],
            'server-export' => [
                'title' => $this->trans()->lang('Export'),
                'handler' => rq(Export::class)->server(),
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
    public function server(string $activeItem = '')
    {
        $this->activeItem = $activeItem;
        $this->render();
    }
}
