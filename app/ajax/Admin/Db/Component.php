<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db;

use Jaxon\Attributes\Attribute\Before;
use Lagdo\DbAdmin\Ajax\Base\Component as BaseComponent;

use function count;

/**
 * Base component for classes that need a db server selected.
 */
#[Before('selectDatabase')]
abstract class Component extends BaseComponent
{
    /**
     * Set the current database
     *
     * @return void
     */
    protected function selectDatabase(): void
    {
        $server = $database = $schema = '';
        $db = $this->getCurrentDb();
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
