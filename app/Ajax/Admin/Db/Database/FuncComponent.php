<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Database;

use Jaxon\Attributes\Attribute\Before;
use Lagdo\DbAdmin\App\Ajax\Base\FuncComponent as BaseComponent;
use Lagdo\DbAdmin\Support\Exception\DriverException;

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
            throw new DriverException('Access to server data is not allowed.');
        }

        $this->driver()->selectDatabase($server, $database, $schema);
    }
}
