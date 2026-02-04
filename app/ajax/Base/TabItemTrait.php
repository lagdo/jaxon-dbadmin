<?php

namespace Lagdo\DbAdmin\Ajax\Base;

use Lagdo\DbAdmin\Ui\TabApp;

/**
 * Item ids for the components in a given tab.
 */
trait TabItemTrait
{
    /**
     * @inheritDoc
     */
    protected function setupComponent(): void
    {
        // Customize the item ids.
        $this->helper()->extend('item', TabApp::item(...));
        // By default, set an id for the component.
        // This will trigger a call to the above extension.
        $this->item('');
    }
}
