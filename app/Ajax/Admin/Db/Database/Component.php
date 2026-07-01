<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Database;

use Jaxon\Attributes\Attribute\Before;
use Lagdo\DbAdmin\App\Ajax\Admin\Page\ContentTrait;
use Lagdo\DbAdmin\App\Ajax\Base\Component as BaseComponent;

#[Before('checkDatabaseAccess')]
abstract class Component extends BaseComponent
{
    use ContentTrait;

    /**
     * Check if the user has access to a server
     *
     * @return void
     */
    protected function checkDatabaseAccess(): void
    {
        [$server, $database, $schema] = $this->getCurrentDb();
        $this->db()->selectDatabase($server, $database, $schema);
    }
}
