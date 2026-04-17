<?php

namespace Lagdo\DbAdmin\App\Ajax\Base;


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
        $this->helper()->extend('item', $this->tab()->app()->item(...));
        // By default, set an id for the component.
        // This will trigger a call to the above extension.
        $this->item('');
    }
}
