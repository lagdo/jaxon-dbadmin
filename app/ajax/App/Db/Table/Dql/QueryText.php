<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Table\Dql;

use function html_entity_decode;

/**
 * This component displays the SQL query.
 *
 * @exclude
 */
class QueryText extends Component
{
    use QueryTrait;

    /**
     * @var string
     */
    private $txtQueryId = 'dbadmin-table-select-query';

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        $query = $this->db()->utils()->html($this->stash()->get('select.query'));
        $query = html_entity_decode($query, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return $this->selectUi->queryText($this->txtQueryId, $query);
    }

    /**
     * @inheritDoc
     */
    protected function after(): void
    {
        [$server, ] = $this->bag('dbadmin')->get('db');
        $this->response->jo('jaxon.dbadmin')
            ->createSqlSelectEditor($this->txtQueryId, $server);
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
