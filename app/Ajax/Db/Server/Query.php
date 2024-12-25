<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Server;

use Jaxon\Response\Response;
use Lagdo\DbAdmin\App\Ajax\Db\Command\QueryTrait;

class Query extends Component
{
    use QueryTrait;

    /**
     * Show the SQL query form for a server
     *
     * @after('call' => 'selectMenuItem', 'with' => ['#adminer-menu-action-server-command', 'adminer-server-actions'])
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
