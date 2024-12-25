<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Server;

use Lagdo\DbAdmin\App\Component as BaseComponent;
use Lagdo\DbAdmin\App\Ajax\Page\Content;
use Lagdo\DbAdmin\Db\Exception\DbException;

/**
 * @before checkServerAccess
 * @after showBreadcrumbs
 */
abstract class Component extends BaseComponent
{
    /**
     * @var string
     */
    protected $overrides = Content::class;

    /**
     * Check if the user has access to a server
     *
     * @return void
     */
    protected function checkServerAccess()
    {
        [$server, $database, $schema] = $this->bag('dbadmin')->get('db');
        $this->db->selectDatabase($server, $database, $schema);
        if(!$this->package->getServerAccess($this->db->getCurrentServer()))
        {
            throw new DbException('Access to server data is forbidden');
        }
    }
}
