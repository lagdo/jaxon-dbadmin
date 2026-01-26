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
            'columns' => $this->getSelectBag('columns', []),
            'filters' => $this->getSelectBag('filters', []),
            'sorting' => $this->getSelectBag('sorting', []),
        ]);
    }
}
