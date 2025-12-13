<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Table\Dql;

use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\Ajax\App\Db\Table\MainComponent;

/**
 * This class displays a row of a select query resultset.
 */
#[Exclude]
class ResultRow extends MainComponent
{
    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return ''; // $this->selectUi->queryForm($this->queryFormId, $this->queryData['fields']);
    }
}
