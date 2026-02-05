<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Database;

use Jaxon\Attributes\Attribute\After;
use Jaxon\Attributes\Attribute\Before;
use Lagdo\DbAdmin\Ajax\Admin\Db\Command\ImportTrait;
use Lagdo\DbAdmin\Ui\Command\ImportUiBuilder;

#[Before('setDatabase')]
class Import extends Component
{
    use ImportTrait;

    /**
     * The constructor
     *
     * @param ImportUiBuilder $importUi The HTML UI builder
     */
    public function __construct(protected ImportUiBuilder $importUi)
    {}

    protected function setDatabase(): void
    {
        [, $database] = $this->getCurrentDb();
        $this->db()->setCurrentDbName($database);
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
