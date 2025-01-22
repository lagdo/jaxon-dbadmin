<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Table\Dql;

use Lagdo\DbAdmin\App\CallableDbClass;

use function html_entity_decode;

/**
 * This class provides select query features on tables.
 */
class Query extends CallableDbClass
{
    /**
     * The select query div id
     *
     * @var string
     */
    private $txtQueryId = 'adminer-table-select-query';

    /**
     * @param string $query
     *
     * @return void
     */
    public function show(string $query)
    {
        [$server, ] = $this->bag('dbadmin')->get('db');
        $this->response->html($this->txtQueryId, '');
        // jaxon.dbadmin.highlightSqlQuery
        $this->response->js('jaxon.dbadmin')->highlightSqlQuery($this->txtQueryId, $server,
            html_entity_decode($query, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }
}
