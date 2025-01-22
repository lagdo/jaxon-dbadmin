<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Server;

use Lagdo\DbAdmin\App\Ajax\Db\Command\ExportTrait;

class Export extends ContentComponent
{
    use ExportTrait;

    /**
     * @inheritDoc
     */
    protected function before()
    {
        $this->activateServerCommandMenu('server-export');
    }

    /**
     * Show the export form for a server
     *
     * @return void
     */
    public function server()
    {
        $this->render();
    }
}
