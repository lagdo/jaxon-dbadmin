<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql;

use Lagdo\DbAdmin\Ajax\Admin\Db\Table\ComponentTrait as BaseTrait;

use Lagdo\DbAdmin\Ui\TabApp;

trait ComponentTrait
{
    use BaseTrait;

    /**
     * Set the default options for the select queries
     *
     * @return void
     */
    protected function setDefaultSelectOptions(): void
    {
        $currentTab = TabApp::current();
        $defaults = $this->bag('dbadmin.select')->get($currentTab, []);

        // Do not change the values if they are already set.
        $defaults['options'] ??= ['limit' => 50, 'total' => true, 'length' => 100];
        $defaults['columns'] ??= [];
        $defaults['filters'] ??= [];
        $defaults['sorting'] ??= [];

        $this->bag('dbadmin.select')->set($currentTab, $defaults);
    }
}
