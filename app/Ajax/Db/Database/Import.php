<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Database;

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
        $this->activateDatabaseCommandMenu('database-import');
    }

    /**
     * Show the import form for a database
     *
     * @after('call' => 'selectMenuItem', 'with' => ['#adminer-menu-action-database-import', 'adminer-database-actions'])
     *
     * @return Response
     */
    public function database(): Response
    {
        [, $this->database] = $this->bag('dbadmin')->get('db');
        return $this->render();
    }
}
