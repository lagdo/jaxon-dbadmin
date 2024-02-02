<?php

namespace Lagdo\DbAdmin\App;

use function count;

/**
 * Callable base for classes that need a db server selected.
 *
 * @before selectDatabase
 */
class CallableDbClass extends CallableClass
{
    /**
     * Set the current database
     *
     * @return void
     */
    protected function selectDatabase()
    {
        $server = $database = $schema = '';
        $db = $this->bag('dbadmin')->get('db', []);
        if(count($db) > 0)
        {
            $server = $db[0];
        }
        if(count($db) > 1)
        {
            $database = $db[1];
        }
        if(count($db) > 2)
        {
            $schema = $db[2];
        }
        $this->db->selectDatabase($server, $database, $schema);
    }
}
