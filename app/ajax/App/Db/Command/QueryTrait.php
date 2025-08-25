<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Command;

use Lagdo\DbAdmin\Ajax\App\Page\PageActions;
use Lagdo\DbAdmin\Ui\Command\QueryUiBuilder;

trait QueryTrait
{
    /**
     * @var QueryUiBuilder
     */
    protected QueryUiBuilder $queryUi;

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
        return $this->queryUi->command($this->query, $defaultLimit, $this->rq());
    }

    /**
     * Called after rendering the component.
     *
     * @return void
     */
    protected function after(): void
    {
        $queryId = 'dbadmin-main-command-query';
        [$server,] = $this->bag('dbadmin')->get('db');
        $this->response->jo('jaxon.dbadmin')->createSqlQueryEditor($queryId, $server);

        // $this->response->jq("#$btnId")->click(jo("jaxon.dbadmin")->saveSqlEditorContent());
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
    public function exec(string $query, array $values): void
    {
        // This will set the breadcrumbs.
        $this->db()->prepareCommand();

        $values['query'] = $query;
        $this->cl(QueryResults::class)->exec($values);
    }
}
