<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Server;

use Lagdo\DbAdmin\App\Component as BaseComponent;
use Lagdo\DbAdmin\App\Ajax\Page\Content;
use Lagdo\DbAdmin\Db\Exception\DbException;

/**
 * @exclude
 * @before('call' => 'checkServerAccess')
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
        if($this->target()->method() === 'connect')
        {
            return; // No check for the connect() method.
        }
        if(!$this->package->getServerAccess($this->db->getCurrentServer()))
        {
            throw new DbException('Access to server data is forbidden');
        }
    }
}
