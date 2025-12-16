<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql;

use Lagdo\DbAdmin\Ajax\Admin\Db\Table\ComponentTrait as BaseTrait;

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
        // Do not change the values if they are already set.
        $this->bag('dbadmin.select')->new('options', ['limit' => 50, 'text_length' => 100]);
        $this->bag('dbadmin.select')->new('columns', []);
        $this->bag('dbadmin.select')->new('filters', []);
        $this->bag('dbadmin.select')->new('sorting', []);
    }
}
