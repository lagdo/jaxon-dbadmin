<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Database;

use Lagdo\DbAdmin\App\Ajax\Db\Command\ImportTrait;

class Import extends ContentComponent
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
     * @return void
     */
    public function database()
    {
        [, $this->database] = $this->bag('dbadmin')->get('db');
        $this->render();
    }
}
