<?php

namespace Lagdo\DbAdmin\App\Ajax;

use Jaxon\Response\Response;
use Lagdo\DbAdmin\App\CallableClass;

use Exception;

use function pm;

/**
 * Adminer Ajax client
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
        $commandOptions = $this->dbAdmin->prepareCommand($server, $database, $schema);

        // Make data available to views
        $this->view()->shareValues($commandOptions);

        // Set main menu buttons
        $content = isset($commandOptions['mainActions']) ?
            $this->uiBuilder->mainActions($commandOptions['mainActions']) : '';
        $this->response->html($this->package->getMainActionsId(), $content);

        $btnId = 'adminer-main-command-execute';
        $formId = 'adminer-main-command-form';
        $queryId = 'adminer-main-command-query';

        $content = $this->render('sql/command', [
            'btnId' => $btnId,
            'formId' => $formId,
            'queryId' => $queryId,
            'defaultLimit' => 20,
            'query' => $query,
        ]);
        $this->response->html($this->package->getDbContentId(), $content);
        $this->response->script("jaxon.adminer.highlightSqlEditor('$queryId', '$server')");

        $this->jq("#$btnId")->click(pm()->js("jaxon.adminer.saveSqlEditorContent"));
        $this->jq("#$btnId")->click($this->rq()->execute($server, $database, $schema,
            pm()->form($formId))->when(pm()->input($queryId)));

        return $this->response;
    }

    /**
     * Show the SQL command form for a server
     *
     * @param string $server      The database server
     * @param string $query       The SQL query to display
     *
     * @return Response
     */
    public function showServerForm(string $server, string $query = ''): Response
    {
        return $this->showForm($server, '', '', $query);
    }

    /**
     * Show the SQL command form for a database
     *
     * @param string $server      The database server
     * @param string $database    The database name
     * @param string $schema      The schema name
     * @param string $query       The SQL query to display
     *
     * @return Response
     */
    public function showDatabaseForm(string $server, string $database = '',
        string $schema = '', string $query = ''): Response
    {
        return $this->showForm($server, $database, $schema, $query);
    }

    /**
     * Execute an SQL query and display the results
     *
     * @param string $server      The database server
     * @param string $database    The database name
     * @param string $schema      The schema name
     * @param array $formValues
     *
     * @return Response
     */
    public function execute(string $server, string $database, string $schema, array $formValues): Response
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

        $queryResults = $this->dbAdmin->executeCommands($server,
            $query, $limit, $errorStops, $onlyErrors, $database, $schema);

        $content = $this->uiBuilder->queryResults($queryResults['results']);
        $this->response->html('adminer-command-results', $content);

        return $this->response;
    }
}
