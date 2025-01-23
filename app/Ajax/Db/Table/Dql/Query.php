<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Table\Dql;

use Lagdo\DbAdmin\App\Ajax\Db\Table\Component;

use function html_entity_decode;

/**
 * This class provides select query features on tables.
 * @exclude
 */
class Query extends Component
{
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
        // jaxon.dbadmin.highlightSqlQuery
        $this->response->js('jaxon.dbadmin')->highlightSqlQuery($this->txtQueryId, $server);
    }

    /**
     * @return void
     */
    public function refresh()
    {
        // Default select options
        $options = $this->bag('dbadmin.select')->get('options');

        // Columns options
        $columns = $this->bag('dbadmin.select')->get('columns', []);
        $options['columns'] = $columns['column'] ?? [];

        // Filter options
        $filters = $this->bag('dbadmin.select')->get('filters', []);
        $options['where'] = $filters['where'] ?? [];

        // Sorting options
        $sorting = $this->bag('dbadmin.select')->get('sorting', []);
        $options['order'] = $sorting['order'] ?? [];
        $options['desc'] = $sorting['desc'] ?? [];

        // Make the new query
        $table = $this->bag('dbadmin')->get('db.table.name');
        $selectData = $this->db->getSelectData($table, $options);

        $this->stash()->set('select.query', $selectData['query']);
        $this->render();
    }
}
