<?php

namespace Lagdo\DbAdmin\Ajax\Base;

/**
 * Item ids for the components in a given tab.
 */
trait TabItemTrait
{
    /**
     * @param string $item
     *
     * @return string
     */
    private function itemTbnId(string $item = ''): string
    {
        $tab = $this->bag('dbadmin')->get('tab.app', 'app-tab-zero');
        return $item === '' ? $tab : "$tab::$item";
    }

    /**
     * Initialize the component
     *
     * @return void
     */
    protected function setupComponent(): void
    {
        // Customize the item ids.
        $this->helper()->extend('item', $this->itemTbnId(...));
        // By default, set an id for the component.
        // This will trigger a call to the above extension.
        $this->item('');
    }
}
