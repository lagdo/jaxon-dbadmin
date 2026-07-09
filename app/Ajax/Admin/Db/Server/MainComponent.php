<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Server;

use Jaxon\Attributes\Attribute\Before;
use Lagdo\DbAdmin\App\Ajax\Admin\Page\ContentTrait;
use Lagdo\DbAdmin\App\Ajax\Base\Component as BaseComponent;
use Lagdo\DbAdmin\Support\Exception\DbException;

#[Before('checkServerAccess')]
abstract class MainComponent extends BaseComponent
{
    use ContentTrait;

    /**
     * Check if the user has access to a server
     *
     * @return void
     */
    protected function checkServerAccess(): void
    {
        [$server, ] = $this->getCurrentDb();
        if(!$this->hasServerAccess($server))
        {
            throw new DbException('Access to server data is not allowed.');
        }

        $this->driver()->selectDatabase($server);
    }
}
