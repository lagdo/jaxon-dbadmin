<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Server;

use Jaxon\Attributes\Attribute\Before;
use Lagdo\DbAdmin\Ajax\App\Page\Content;
use Lagdo\DbAdmin\Ajax\Component as BaseComponent;
use Lagdo\DbAdmin\Db\Exception\DbException;

#[Before('checkServerAccess')]
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
    protected function checkServerAccess(): void
    {
        [$server, ] = $this->bag('dbadmin')->get('db');
        if(!$this->hasServerAccess($server))
        {
            throw new DbException('Access to server data is not allowed.');
        }

        $this->db()->selectDatabase($server);
    }
}
