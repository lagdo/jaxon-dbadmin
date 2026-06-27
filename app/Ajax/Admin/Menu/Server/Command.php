<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Menu\Server;

use Lagdo\DbAdmin\App\Ajax\Admin\Db\Server\Export;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Server\Import;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Server\QueryEditor;
use Lagdo\DbAdmin\App\Ajax\Base\MenuComponent;

use function Jaxon\rq;

class Command extends MenuComponent
{
    /**
     * @inheritDoc
     */
    public function html(): string
    {
        $actions = [
            'server-query' => [
                'title' => $this->trans()->lang('Query'),
                'handler' => rq(QueryEditor::class)->server(),
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

        return $this->ui()->commands($actions, $this->get('active', ''));
    }
}
