<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Table;

use Lagdo\DbAdmin\Db\Exception\DbException;

trait CallableTrait
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
     * Get the current table name
     *
     * @return string
     */
    protected function getTableName(): string
    {
        return $this->bag('dbadmin')->get('db.table.name');
    }
}
