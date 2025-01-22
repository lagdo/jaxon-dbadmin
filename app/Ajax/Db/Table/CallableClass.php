<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Table;

use Lagdo\DbAdmin\App\CallableClass as BaseCallableClass;
use Lagdo\DbAdmin\App\Ajax\Page\Content;
use Lagdo\DbAdmin\Db\Exception\DbException;

/**
 * @before checkDatabaseAccess
 * @after showBreadcrumbs
 */
abstract class CallableClass extends BaseCallableClass
{
    /**
     * Check if the user has access to a server
     *
     * @return void
     */
    protected function checkDatabaseAccess()
    {
        [$server, $database, $schema] = $this->bag('dbadmin')->get('db');
        $this->db->selectDatabase($server, $database, $schema);
        if(!$this->package->getServerAccess($this->db->getCurrentServer()))
        {
            throw new DbException('Access to database data is forbidden');
        }
    }
}
