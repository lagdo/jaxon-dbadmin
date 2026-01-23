<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Database;

use Jaxon\Attributes\Attribute\Before;
use Lagdo\DbAdmin\Ajax\Admin\Page\Content;
use Lagdo\DbAdmin\Ajax\Base\Component as BaseComponent;

#[Before('checkDatabaseAccess')]
abstract class Component extends BaseComponent
{
    /**
     * @var string
     */
    protected string $overrides = Content::class;

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
}
