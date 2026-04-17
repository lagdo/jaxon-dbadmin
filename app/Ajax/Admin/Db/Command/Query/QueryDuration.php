<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Command\Query;

use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\App\Ajax\Base\Duration as BaseDuration;

/**
 * This component displays the SQL query duration.
 */
#[Exclude]
class QueryDuration extends BaseDuration
{
    /**
     * @inheritDoc
     */
    protected function setupComponent(): void
    {
        // Customize the item ids.
        $this->helper()->extend('item', $this->tab()->editor()->item(...));
        // By default, set an id for the component.
        // This will trigger a call to the above extension.
        $this->item('');
    }
}
