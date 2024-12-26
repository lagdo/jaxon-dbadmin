<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Server;

use Jaxon\Response\Response;
use Lagdo\DbAdmin\App\Ajax\Db\Command\ExportTrait;

class Export extends Component
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
     * @after('call' => 'selectMenuItem', 'with' => ['#adminer-menu-action-server-export', 'adminer-server-actions'])
     *
     * @return Response
     */
    public function server(): Response
    {
        return $this->render();
    }
}
