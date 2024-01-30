<?php

namespace Lagdo\DbAdmin\App\Ajax;

use Jaxon\Response\Response;
use Lagdo\DbAdmin\App\CallableClass;

use Exception;

use function Jaxon\pm;

/**
 * @databag selection
 */
class Command extends CallableClass
{
    /**
     * Show the SQL command form
     *
     * @param string $server      The database server
     * @param string $database    The database name
     * @param string $schema      The schema name
     * @param string $query       The SQL query to display
     *
     * @return Response
     */
    protected function showForm(string $server, string $database, string $schema, string $query): Response
    {
        $commandOptions = $this->db->prepareCommand($server, $database, $schema);

        // Make data available to views
        $this->view()->shareValues($commandOptions);

        // Set main menu buttons
        $content = isset($commandOptions['mainActions']) ?
            $this->ui->mainActions($commandOptions['mainActions']) : '';
        $this->response->html($this->package->getMainActionsId(), $content);

        $btnId = 'adminer-main-command-execute';
        $formId = 'adminer-main-command-form';
        $queryId = 'adminer-main-command-query';

        $defaultLimit = 20;
        $content = $this->ui->queryCommand($formId, $queryId, $btnId,
            $query, $defaultLimit, $commandOptions['labels']);
        $this->response->html($this->package->getDbContentId(), $content);
        $this->response->script("jaxon.dbadmin.highlightSqlEditor('$queryId', '$server')");

        $this->jq("#$btnId")->click(pm()->js("jaxon.dbadmin.saveSqlEditorContent"));
        $this->jq("#$btnId")->click($this->rq()->execute(pm()->form($formId))
            ->when(pm()->input($queryId)));

        return $this->response;
    }

    /**
     * Show the SQL command form for a server
     *
     * @after('call' => 'showBreadcrumbs')
     * @after('call' => 'selectMenuItem', 'with' => ['#adminer-menu-action-server-command', 'adminer-server-actions'])
     *
     * @param string $query       The SQL query to display
     *
     * @return Response
     */
    public function showServerForm(string $query = ''): Response
    {
        [$server,] = $this->bag('selection')->get('db');
        return $this->showForm($server, '', '', $query);
    }

    /**
     * Show the SQL command form for a database
     *
     * @after('call' => 'showBreadcrumbs')
     * @after('call' => 'selectMenuItem', 'with' => ['#adminer-menu-action-database-command', 'adminer-database-actions'])
     *
     * @param string $query       The SQL query to display
     *
     * @return Response
     */
    public function showDatabaseForm(string $query = ''): Response
    {
        [$server, $database, $schema] = $this->bag('selection')->get('db');
        return $this->showForm($server, $database, $schema, $query);
    }

    /**
     * Execute an SQL query and display the results
     *
     * @after('call' => 'debugQueries')
     *
     * @param array $formValues
     *
     * @return Response
     */
    public function execute(array $formValues): Response
    {
        $query = \trim($formValues['query'] ?? '');
        $limit = \intval($formValues['limit'] ?? 0);
        $errorStops = $formValues['error_stops'] ?? false;
        $onlyErrors = $formValues['only_errors'] ?? false;

        if(!$query)
        {
            $this->response->dialog->error('The query string is empty!', 'Error');
            return $this->response;
        }

        [$server, $database, $schema] = $this->bag('selection')->get('db');
        $queryResults = $this->db->executeCommands($server,
            $query, $limit, $errorStops, $onlyErrors, $database, $schema);

        $content = $this->ui->queryResults($queryResults['results']);
        $this->response->html('adminer-command-results', $content);

        return $this->response;
    }
}
