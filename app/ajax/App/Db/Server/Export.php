<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Server;

use Lagdo\DbAdmin\Ajax\App\Db\Command\ExportTrait;

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
