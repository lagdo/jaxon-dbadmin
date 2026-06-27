<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Menu\Database;

use Lagdo\DbAdmin\App\Ajax\Admin\Db\Database\Export;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Database\Import;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Database\QueryEditor;
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
            'database-query' => [
                'title' => $this->trans()->lang('Query'),
                'handler' => rq(QueryEditor::class)->database(),
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

        return $this->ui()->commands($actions, $this->get('active', ''));
    }
}
