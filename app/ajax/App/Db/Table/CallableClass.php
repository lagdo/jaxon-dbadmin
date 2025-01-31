<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Table;

use Lagdo\DbAdmin\Ajax\CallableClass as BaseCallableClass;

/**
 * @databag dbadmin.select
 * @before checkDatabaseAccess
 * @before setDefaultSelectOptions
 * @after showBreadcrumbs
 */
abstract class CallableClass extends BaseCallableClass
{
    use CallableTrait;

    /**
     * Set the default options for the select queries
     *
     * @return void
     */
    protected function setDefaultSelectOptions()
    {
        // Do not change the values if they are already set.
        $this->bag('dbadmin.select')->new('options', ['limit' => 50, 'text_length' => 100]);
        $this->bag('dbadmin.columns')->new('', []);
        $this->bag('dbadmin.filters')->new('', []);
        $this->bag('dbadmin.sorting')->new('', []);
    }
}
