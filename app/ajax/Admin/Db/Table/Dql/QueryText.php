<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql;

use Jaxon\Attributes\Attribute\Exclude;
use Lagdo\DbAdmin\Db\DbAdminPackage;
use Lagdo\DbAdmin\Db\Driver\DbFacade;
use Lagdo\DbAdmin\Driver\Utils\Utils;
use Lagdo\DbAdmin\Ui\Select\SelectUiBuilder;

use function html_entity_decode;

/**
 * This component displays the SQL query.
 */
#[Exclude]
class QueryText extends Component
{
    use QueryTrait;

    /**
     * The constructor
     *
     * @param DbFacade        $db         The facade to database functions
     * @param Utils           $utils
     * @param DbAdminPackage  $package    The DbAdmin package
     * @param SelectUiBuilder $selectUi The HTML UI builder
     */
    public function __construct(protected DbFacade $db, protected Utils $utils,
        protected DbAdminPackage $package, protected SelectUiBuilder $selectUi)
    {}

    /**
     * @var string
     */
    private $txtQueryId = 'dbadmin-table-select-query';

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        $query = $this->utils->html($this->stash()->get('select.query'));
        $query = html_entity_decode($query, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return $this->selectUi->queryText($this->txtQueryId, $query);
    }

    /**
     * @inheritDoc
     */
    protected function after(): void
    {
        [$server, ] = $this->bag('dbadmin')->get('db');
        $driver = $this->package->getServerDriver($server);
        $this->response->jo('jaxon.dbadmin')->createSqlSelectEditor($this->txtQueryId, $driver);
    }

    /**
     * @return void
     */
    public function refresh(): void
    {
        $this->stash()->set('select.query', $this->getSelectQuery());
        $this->render();
    }
}
