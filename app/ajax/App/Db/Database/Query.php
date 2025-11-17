<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Database;

use Jaxon\Attributes\Attribute\After;
use Lagdo\DbAdmin\Ajax\App\Db\Command\Query\QueryTrait;
use Lagdo\DbAdmin\DbAdminPackage;
use Lagdo\DbAdmin\Db\DbFacade;
use Lagdo\DbAdmin\Translator;
use Lagdo\DbAdmin\Ui\Command\QueryUiBuilder;

class Query extends Component
{
    use QueryTrait;

    /**
     * The constructor
     *
     * @param DbAdminPackage $package    The DbAdmin package
     * @param DbFacade       $db         The facade to database functions
     * @param QueryUiBuilder $queryUi    The HTML UI builder
     * @param Translator     $trans
     */
    public function __construct(protected DbAdminPackage $package, protected DbFacade $db,
        protected QueryUiBuilder $queryUi, protected Translator $trans)
    {}

    /**
     * @inheritDoc
     */
    protected function before(): void
    {
        $this->activateDatabaseCommandMenu('database-query');
    }

    /**
     * Show the SQL command form for a database
     *
     * @param string $query       The SQL query to display
     *
     * @return void
     */
    #[After('showBreadcrumbs')]
    public function database(string $query = ''): void
    {
        [, $this->database] = $this->bag('dbadmin')->get('db');
        $this->query = $query;
        $this->render();
    }
}
