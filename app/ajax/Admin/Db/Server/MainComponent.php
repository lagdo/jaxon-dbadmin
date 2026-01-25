<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Server;

use Jaxon\Attributes\Attribute\Before;
use Lagdo\DbAdmin\Ajax\Base\Component as BaseComponent;
use Lagdo\DbAdmin\Ajax\Admin\Page\Content;
use Lagdo\DbAdmin\Db\Driver\Exception\DbException;

#[Before('checkServerAccess')]
abstract class MainComponent extends BaseComponent
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
    protected function checkServerAccess(): void
    {
        [$server, ] = $this->currentDb();
        if(!$this->hasServerAccess($server))
        {
            throw new DbException('Access to server data is not allowed.');
        }

        $this->db()->selectDatabase($server);
    }
}
