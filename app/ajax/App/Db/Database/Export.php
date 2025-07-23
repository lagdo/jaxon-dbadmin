<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Database;

use Lagdo\DbAdmin\Ajax\App\Db\Command\ExportTrait;

class Export extends ContentComponent
{
    use ExportTrait;

    /**
     * @inheritDoc
     */
    protected function before(): void
    {
        $this->activateDatabaseCommandMenu('database-export');
    }

    /**
     * Show the export form for a database
     *
     * @return void
     */
    public function database(): void
    {
        [, $this->database] = $this->bag('dbadmin')->get('db');
        $this->render();
    }
}
