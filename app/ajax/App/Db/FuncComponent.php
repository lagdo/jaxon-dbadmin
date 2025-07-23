<?php

namespace Lagdo\DbAdmin\Ajax\App\Db;

use Lagdo\DbAdmin\Ajax\FuncComponent as BaseComponent;

use function count;

/**
 * Base component for classes that need a db server selected.
 *
 * @before selectDatabase
 */
class FuncComponent extends BaseComponent
{
    /**
     * Set the current database
     *
     * @return void
     */
    protected function selectDatabase(): void
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
        $this->db()->selectDatabase($server, $database, $schema);
    }
}
