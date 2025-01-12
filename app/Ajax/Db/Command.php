<?php

namespace Lagdo\DbAdmin\App\Ajax\Db;

use Lagdo\DbAdmin\App\CallableDbClass;
use Lagdo\DbAdmin\App\Ajax\Page\Content;
use Lagdo\DbAdmin\App\Ajax\Page\PageActions;

use function Jaxon\js;
use function Jaxon\pm;

class Command extends CallableDbClass
{
    /**
     * Show the SQL command form
     *
     * @param string $query       The SQL query to display
     * @param string $database    The database name
     *
     * @return void
     */
    protected function showForm(string $query, string $database = '')
    {
        // Set the current database, but do not update the databag.
        $this->db->setCurrentDbName($database);

        $commandOptions = $this->db->prepareCommand();

        // Make data available to views
        $this->view()->shareValues($commandOptions);

        // Set main menu buttons
        $this->cl(PageActions::class)->clear();

        $btnId = 'adminer-main-command-execute';
        $formId = 'adminer-main-command-form';
        $queryId = 'adminer-main-command-query';

        $defaultLimit = 20;
        [$server,] = $this->bag('dbadmin')->get('db');
        $content = $this->ui->queryCommand($formId, $queryId, $btnId,
            $query, $defaultLimit, $commandOptions['labels']);
        $this->cl(Content::class)->showHtml($content);

        $this->response->addCommand('dbadmin.hsqleditor', [
            'id' => $queryId,
            'server' => $server,
        ]);

        $this->response->jq("#$btnId")->click(js("jaxon.dbadmin")->saveSqlEditorContent());
        $this->response->jq("#$btnId")->click($this->rq()->execute(pm()->form($formId))
            ->when(pm()->input($queryId)));
    }

    /**
     * Show the SQL command form for a server
     *
     * @after('call' => 'showBreadcrumbs')
     * @after('call' => 'selectMenuItem', 'with' => ['#adminer-menu-action-server-command', 'adminer-server-actions'])
     *
     * @param string $query       The SQL query to display
     *
     * @return void
     */
    public function showServerForm(string $query = '')
    {
        $this->showForm($query);
    }

    /**
     * Show the SQL command form for a database
     *
     * @after('call' => 'showBreadcrumbs')
     * @after('call' => 'selectMenuItem', 'with' => ['#adminer-menu-action-database-command', 'adminer-database-actions'])
     *
     * @param string $query       The SQL query to display
     *
     * @return void
     */
    public function showDatabaseForm(string $query = '')
    {
        [, $database] = $this->bag('dbadmin')->get('db');
        $this->showForm($query, $database);
    }

    /**
     * Execute an SQL query and display the results
     *
     * @after('call' => 'debugQueries')
     *
     * @param array $formValues
     *
     * @return void
     */
    public function execute(array $formValues)
    {
        $query = \trim($formValues['query'] ?? '');
        $limit = \intval($formValues['limit'] ?? 0);
        $errorStops = $formValues['error_stops'] ?? false;
        $onlyErrors = $formValues['only_errors'] ?? false;

        if(!$query)
        {
            $this->response->dialog->error('The query string is empty!', 'Error');
            return;
        }

        $queryResults = $this->db->executeCommands($query, $limit, $errorStops, $onlyErrors);

        $content = $this->ui->queryResults($queryResults['results']);
        $this->response->html('adminer-command-results', $content);
    }
}
