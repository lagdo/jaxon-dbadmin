<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Table\Dql;

use Lagdo\DbAdmin\App\Ajax\Db\Table\Component;

use function html_entity_decode;

/**
 * This component displays the SQL query.
 */
class QueryText extends Component
{
    use QueryTrait;

    /**
     * The select query div id
     *
     * @var string
     */
    private $txtQueryId = 'adminer-table-select-query';

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        $query = $this->stash()->get('select.query');
        return html_entity_decode($query, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * @inheritDoc
     */
    protected function after()
    {
        [$server, ] = $this->bag('dbadmin')->get('db');
        // jaxon.dbadmin.refreshSqlQuery
        $this->response->js('jaxon.dbadmin')->refreshSqlQuery($this->txtQueryId, $server);
    }

    /**
     * @return void
     */
    public function refresh()
    {
        $this->stash()->set('select.query', $this->getSelectQuery());
        $this->render();
    }
}
