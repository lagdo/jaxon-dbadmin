<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table;

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
