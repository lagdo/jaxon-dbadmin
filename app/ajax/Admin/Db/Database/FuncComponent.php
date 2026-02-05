<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Database;

use Jaxon\Attributes\Attribute\Before;
use Lagdo\DbAdmin\Ajax\Base\FuncComponent as BaseComponent;
use Lagdo\DbAdmin\Db\Driver\Exception\DbException;

#[Before('checkDatabaseAccess')]
abstract class FuncComponent extends BaseComponent
{
    /**
     * Check if the user has access to a server
     *
     * @return void
     */
    protected function checkDatabaseAccess(): void
    {
        [$server, $database, $schema] = $this->getCurrentDb();
        if(!$this->hasServerAccess($server))
        {
            throw new DbException('Access to server data is not allowed.');
        }

        $this->db()->selectDatabase($server, $database, $schema);
    }
}
