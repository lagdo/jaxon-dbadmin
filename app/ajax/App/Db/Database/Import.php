<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Database;

use Lagdo\DbAdmin\Ajax\App\Db\Command\ImportTrait;

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
