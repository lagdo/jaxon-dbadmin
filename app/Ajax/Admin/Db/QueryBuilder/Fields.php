<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\QueryBuilder;

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
        return $this->optionsUi->optionsFields($this->getBuilderParams());
    }
}
