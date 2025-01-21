<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Command;

use Lagdo\DbAdmin\App\Ajax\Page\PageActions;

use function Jaxon\js;
use function Jaxon\pm;

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
        $this->db->setCurrentDbName($this->database);

        $commandOptions = $this->db->prepareCommand();

        // Make data available to views
        $this->view()->shareValues($commandOptions);

        // Set main menu buttons
        $this->cl(PageActions::class)->clear();

        $btnId = 'adminer-main-command-execute';
        $formId = 'adminer-main-command-form';
        $queryId = 'adminer-main-command-query';

        $defaultLimit = 20;
        return $this->ui->queryCommand($formId, $queryId, $btnId,
            $this->query, $defaultLimit, $commandOptions['labels']);
    }

    /**
     * Called after rendering the component.
     *
     * @return void
     */
    protected function after()
    {
        $btnId = 'adminer-main-command-execute';
        $formId = 'adminer-main-command-form';
        $queryId = 'adminer-main-command-query';
        [$server,] = $this->bag('dbadmin')->get('db');
        $this->response->addCommand('dbadmin.hsqleditor', [
            'id' => $queryId,
            'server' => $server,
        ]);

        $this->response->jq("#$btnId")->click(js("jaxon.dbadmin")->saveSqlEditorContent());
        $this->response->jq("#$btnId")->click($this->rq()->execute(pm()->form($formId))
            ->when(pm()->input($queryId)));
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
            $this->alert()->title('Error')->error('The query string is empty!');
            return;
        }

        $queryResults = $this->db->executeCommands($query, $limit, $errorStops, $onlyErrors);

        $content = $this->ui->queryResults($queryResults['results']);
        $this->response->html('adminer-command-results', $content);
    }
}
