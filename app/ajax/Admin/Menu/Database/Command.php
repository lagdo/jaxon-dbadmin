<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Menu\Database;

use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\Ajax\Admin\Db\Database\Export;
use Lagdo\DbAdmin\Ajax\Admin\Db\Database\Import;
use Lagdo\DbAdmin\Ajax\Admin\Db\Database\Query;
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

        return $this->ui()->commands($actions, $this->activeItem);
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
        $this->render();
    }
}
