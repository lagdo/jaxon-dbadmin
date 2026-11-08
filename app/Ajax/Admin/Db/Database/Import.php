<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Database;

use Jaxon\Attributes\Attribute\After;
use Jaxon\Attributes\Attribute\Before;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Command\ImportTrait;

#[Before('setDatabase')]
class Import extends Component
{
    use ImportTrait;

    protected function setDatabase(): void
    {
        [, $database] = $this->getCurrentDb();
        $this->driver()->setCurrentDbName($database);
    }

    /**
     * @inheritDoc
     */
    protected function before(): void
    {
        $this->activateDatabaseCommandMenu('database-import');
    }

    /**
     * Show the import form for a database
     *
     * @return void
     */
    #[After('showBreadcrumbs')]
    public function database(): void
    {
        $this->render();
    }
}
