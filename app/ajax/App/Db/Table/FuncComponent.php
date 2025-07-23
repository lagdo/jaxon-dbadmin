<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Table;

use Lagdo\DbAdmin\Ajax\FuncComponent as BaseFuncComponent;

/**
 * @databag dbadmin.select
 * @before checkDatabaseAccess
 * @before setDefaultSelectOptions
 * @after showBreadcrumbs
 */
abstract class FuncComponent extends BaseFuncComponent
{
    use ComponentTrait;

    /**
     * Set the default options for the select queries
     *
     * @return void
     */
    protected function setDefaultSelectOptions(): void
    {
        // Do not change the values if they are already set.
        $this->bag('dbadmin.select')->new('options', ['limit' => 50, 'text_length' => 100]);
        $this->bag('dbadmin.columns')->new('', []);
        $this->bag('dbadmin.filters')->new('', []);
        $this->bag('dbadmin.sorting')->new('', []);
    }
}
