<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Database;

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
        $this->activateDatabaseCommandMenu('database-export');
    }

    /**
     * Show the export form for a database
     *
     * @return Response
     */
    public function database(): Response
    {
        [, $this->database] = $this->bag('dbadmin')->get('db');
        return $this->render();
    }
}
