<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql\Options;

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
        return $this->optionsUi->optionsFields([
            'columns' => $this->bag('dbadmin.select')->get($this->tabKey('columns'), []),
            'filters' => $this->bag('dbadmin.select')->get($this->tabKey('filters'), []),
            'sorting' => $this->bag('dbadmin.select')->get($this->tabKey('sorting'), []),
        ]);
    }
}
