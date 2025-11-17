<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Database;

use Jaxon\Attributes\Attribute\After;
use Jaxon\Attributes\Attribute\Before;
use Lagdo\DbAdmin\Ajax\App\Db\Command\ImportTrait;
use Lagdo\DbAdmin\DbAdminPackage;
use Lagdo\DbAdmin\Db\DbFacade;
use Lagdo\DbAdmin\Translator;
use Lagdo\DbAdmin\Ui\Command\ImportUiBuilder;

#[Before('setDatabase')]
class Import extends Component
{
    use ImportTrait;

    /**
     * The constructor
     *
     * @param DbAdminPackage  $package    The DbAdmin package
     * @param DbFacade        $db         The facade to database functions
     * @param ImportUiBuilder $importUi The HTML UI builder
     * @param Translator      $trans
     */
    public function __construct(protected DbAdminPackage $package, protected DbFacade $db,
        protected ImportUiBuilder $importUi, protected Translator $trans)
    {}

    protected function setDatabase(): void
    {
        [, $database] = $this->bag('dbadmin')->get('db');
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
