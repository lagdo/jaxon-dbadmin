<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Command;

use Lagdo\DbAdmin\Ajax\App\Page\PageActions;

trait QueryTrait
{
    /**
     * @var string
     */
    private $database = '';

    /**
     * @var string
     */
    private $query = '';

    /**
     * @return string
     */
    public function html(): string
    {
        // Set the current database, but do not update the databag.
        $this->db()->setCurrentDbName($this->database);

        $this->db()->prepareCommand();

        // Set main menu buttons
        $this->cl(PageActions::class)->clear();

        $defaultLimit = 20;
        return $this->ui()->queryCommand($this->query, $defaultLimit, $this->rq());
    }

    /**
     * Called after rendering the component.
     *
     * @return void
     */
    protected function after()
    {
        $queryId = 'dbadmin-main-command-query';
        [$server,] = $this->bag('dbadmin')->get('db');
        $this->response->js('jaxon.dbadmin')->createSqlEditor($queryId, $server);

        // $this->response->jq("#$btnId")->click(js("jaxon.dbadmin")->saveSqlEditorContent());
    }

    /**
     * Execute an SQL query and display the results
     *
     * @after('call' => 'debugQueries')
     *
     * @param string $query
     * @param array $values
     *
     * @return void
     */
    public function exec(string $query, array $values)
    {
        // This will set the breadcrumbs.
        $this->db()->prepareCommand();

        $values['query'] = $query;
        $this->cl(QueryResults::class)->exec($values);
    }
}
