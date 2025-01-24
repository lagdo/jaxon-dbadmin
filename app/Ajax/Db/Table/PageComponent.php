<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Table;

use Lagdo\DbAdmin\App\PageComponent as BaseComponent;
use Lagdo\DbAdmin\Db\Exception\DbException;

/**
 * @databag dbadmin.select
 * @before checkDatabaseAccess
 */
abstract class PageComponent extends BaseComponent
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

    /**
     * @inheritDoc
     */
    protected function limit(): int
    {
        return $this->bag('dbadmin.select')->get('options', [])['limit'] ?? 50;
    }
}
