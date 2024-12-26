<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Server;

use Jaxon\Response\Response;
use Lagdo\DbAdmin\App\Ajax\Db\Command\ImportTrait;

class Import extends Component
{
    use ImportTrait;

    /**
     * @inheritDoc
     */
    protected function before()
    {
        $this->activateServerCommandMenu('server-import');
    }

    /**
     * Show the import form for a server
     *
     * @after('call' => 'selectMenuItem', 'with' => ['#adminer-menu-action-server-import', 'adminer-server-actions'])
     *
     * @return Response
     */
    public function server(): Response
    {
        return $this->render();
    }
}
