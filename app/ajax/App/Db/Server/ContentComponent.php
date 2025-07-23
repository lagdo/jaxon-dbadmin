<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Server;

use Lagdo\DbAdmin\Ajax\Component as BaseComponent;
use Lagdo\DbAdmin\Ajax\App\Page\Content;
use Lagdo\DbAdmin\Db\Exception\DbException;

/**
 * @before checkServerAccess
 * @after showBreadcrumbs
 */
abstract class ContentComponent extends BaseComponent
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
    protected function checkServerAccess(): void
    {
        [$server, ] = $this->bag('dbadmin')->get('db');
        $this->db()->selectDatabase($server);
        if(!$this->package()->getServerAccess($this->db()->getCurrentServer()))
        {
            throw new DbException('Access to server data is forbidden');
        }
    }
}
