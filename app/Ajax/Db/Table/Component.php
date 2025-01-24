<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Table;

use Lagdo\DbAdmin\App\Component as BaseComponent;
use Lagdo\DbAdmin\Db\Exception\DbException;

/**
 * @databag dbadmin.select
 * @before checkDatabaseAccess
 * @before setDefaultSelectOptions
 * @after showBreadcrumbs
 */
abstract class Component extends BaseComponent
{
    /**
     * Check if the user has access to a server
     *
     * @return void
     */
    protected function checkDatabaseAccess()
    {
        [$server, $database, $schema] = $this->bag('dbadmin')->get('db');
        $this->db()->selectDatabase($server, $database, $schema);
        if(!$this->package()->getServerAccess($this->db()->getCurrentServer()))
        {
            throw new DbException('Access to database data is forbidden');
        }
    }

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
