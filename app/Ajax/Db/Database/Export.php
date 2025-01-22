<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Database;

use Lagdo\DbAdmin\App\Ajax\Db\Command\ExportTrait;

class Export extends ContentComponent
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
     * @return void
     */
    public function database()
    {
        [, $this->database] = $this->bag('dbadmin')->get('db');
        $this->render();
    }
}
