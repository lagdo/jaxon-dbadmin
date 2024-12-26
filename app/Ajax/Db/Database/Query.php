<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Database;

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
        $this->activateDatabaseCommandMenu('database-query');
    }

    /**
     * Show the SQL command form for a database
     *
     * @after('call' => 'selectMenuItem', 'with' => ['#adminer-menu-action-database-command', 'adminer-database-actions'])
     *
     * @param string $query       The SQL query to display
     *
     * @return Response
     */
    public function database(string $query = ''): Response
    {
        [, $this->database] = $this->bag('dbadmin')->get('db');
        $this->query = $query;
        return $this->render();
    }
}
