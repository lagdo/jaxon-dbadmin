<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Menu\Server;

use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\Ajax\Admin\Db\Server\Export;
use Lagdo\DbAdmin\Ajax\Admin\Db\Server\Import;
use Lagdo\DbAdmin\Ajax\Admin\Db\Server\Query;
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

        return $this->ui()->commands($actions, $this->activeItem);
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
        $this->render();
    }
}
