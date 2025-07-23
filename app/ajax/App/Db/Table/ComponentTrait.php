<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Table;

use Lagdo\DbAdmin\Db\Exception\DbException;

trait ComponentTrait
{
    /**
     * Check if the user has access to a server
     *
     * @return void
     */
    protected function checkDatabaseAccess(): void
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
