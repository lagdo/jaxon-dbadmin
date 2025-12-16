<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql;

use Jaxon\Attributes\Attribute\Exclude;

/**
 * This class displays a row of a select query resultset.
 */
#[Exclude]
class ResultRow extends MainComponent
{
    /**
     * @var string
     */
    protected $overrides = '';

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->selectUi->resultRowContent($this->stash()->get('select.result'));
    }
}
