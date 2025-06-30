<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Table;

use Lagdo\DbAdmin\Ajax\Component as BaseComponent;

/**
 * @databag dbadmin.select
 * @before checkDatabaseAccess
 * @before setDefaultSelectOptions
 * @after showBreadcrumbs
 */
abstract class Component extends BaseComponent
{
    use ComponentTrait;

    /**
     * Set the default options for the select queries
     *
     * @return void
     */
    protected function setDefaultSelectOptions()
    {
        // Do not change the values if they are already set.
        $this->bag('dbadmin.select')->new('options', ['limit' => 50, 'text_length' => 100]);
        $this->bag('dbadmin.select')->new('columns', []);
        $this->bag('dbadmin.select')->new('filters', []);
        $this->bag('dbadmin.select')->new('sorting', []);
    }
}
