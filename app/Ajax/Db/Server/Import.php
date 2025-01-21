<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Server;

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
     * @return void
     */
    public function server()
    {
        $this->render();
    }
}
