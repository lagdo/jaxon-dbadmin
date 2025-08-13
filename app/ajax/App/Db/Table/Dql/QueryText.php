<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Table\Dql;

use function html_entity_decode;

/**
 * This component displays the SQL query.
 */
class QueryText extends Component
{
    use QueryTrait;

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        $query = $this->db()->utils()->html($this->stash()->get('select.query'));
        return html_entity_decode($query, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * @inheritDoc
     */
    protected function after(): void
    {
        [$server, ] = $this->bag('dbadmin')->get('db');
        $txtQueryId = 'dbadmin-table-select-query';
        $this->response->jo('jaxon.dbadmin')->refreshSqlQuery($txtQueryId, $server);
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
