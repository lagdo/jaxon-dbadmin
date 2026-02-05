<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Command\Query;

use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\Ajax\Base\Duration as BaseDuration;
use Lagdo\DbAdmin\Ui\TabEditor;

/**
 * This component displays the SQL query duration.
 */
#[Exclude]
class Duration extends BaseDuration
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
}
