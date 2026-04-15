<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Command\Query;

use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\App\Ajax\Base\Duration;
use Lagdo\DbAdmin\App\Ui\TabEditor;

#[Exclude]
class QueryResult extends ImportResult
{
    /**
     * @inheritDoc
     */
    protected function setupComponent(): void
    {
        // Customize the item ids.
        $this->helper()->extend('item', TabEditor::item(...));
        // By default, set an id for the component.
        // This will trigger a call to the above extension.
        $this->item('');
    }

    /**
     * @return Duration
     */
    protected function duration(): Duration
    {
        return $this->cl(QueryDuration::class);
    }
}
