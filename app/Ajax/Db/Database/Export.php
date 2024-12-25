<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Database;

use Jaxon\Response\Response;
use Lagdo\DbAdmin\App\Ajax\Db\Command\ExportTrait;

class Export extends Component
{
    use ExportTrait;

    /**
     * Show the export form for a database
     *
     * @after('call' => 'selectMenuItem', 'with' => ['#adminer-menu-action-database-export', 'adminer-database-actions'])
     *
     * @return Response
     */
    public function database(): Response
    {
        [, $this->database] = $this->bag('dbadmin')->get('db');
        return $this->render();
    }
}
