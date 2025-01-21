<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Server;

use Jaxon\Response\Response;
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
     * @return Response
     */
    public function server(string $query = ''): Response
    {
        $this->query = $query;
        return $this->render();
    }
}
