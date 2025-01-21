<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Server;

use Lagdo\DbAdmin\App\Ajax\Db\Command\QueryTrait;

class Query extends Component
{
    use QueryTrait;

    /**
     * @inheritDoc
     */
    protected function before()
    {
        $this->activateServerCommandMenu('server-query');
    }

    /**
     * Show the SQL query form for a server
     *
     * @param string $query       The SQL query to display
     *
     * @return void
     */
    public function server(string $query = '')
    {
        $this->query = $query;
        $this->render();
    }
}
