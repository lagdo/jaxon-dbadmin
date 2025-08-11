<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Table\Dql\Options;

use Lagdo\DbAdmin\Ajax\App\Db\Table\Dql\Component;

/**
 * This class provides select query features on tables.
 */
class Fields extends Component
{
    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->html->optionsFields([
            'columns' => $this->bag('dbadmin.select')->get('columns', []),
            'filters' => $this->bag('dbadmin.select')->get('filters', []),
            'sorting' => $this->bag('dbadmin.select')->get('sorting', []),
        ]);
    }
}
