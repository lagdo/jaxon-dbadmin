<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Database;

use Lagdo\DbAdmin\Ajax\App\Db\Command\QueryTrait;

class Query extends ContentComponent
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
     * @param string $query       The SQL query to display
     *
     * @return void
     */
    public function database(string $query = '')
    {
        [, $this->database] = $this->bag('dbadmin')->get('db');
        $this->query = $query;
        $this->render();
    }
}
