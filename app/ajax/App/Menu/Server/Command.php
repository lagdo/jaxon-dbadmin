<?php

namespace Lagdo\DbAdmin\Ajax\App\Menu\Server;

use Lagdo\DbAdmin\Ajax\App\Db\Server\Export;
use Lagdo\DbAdmin\Ajax\App\Db\Server\Import;
use Lagdo\DbAdmin\Ajax\App\Db\Server\Query;
use Lagdo\DbAdmin\Ajax\MenuComponent;

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
    public function server(string $activeItem = ''): void
    {
        $this->activeItem = $activeItem;
        $this->render();
    }
}
