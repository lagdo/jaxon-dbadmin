<?php

namespace Lagdo\DbAdmin\App\Ajax;

use Jaxon\Response\Response;
use Lagdo\DbAdmin\App\CallableClass;

use Exception;

use function compact;

class Import extends CallableClass
{
    /**
     * Show the import form
     *
     * @param string $server      The database server
     * @param string $database    The database name
     *
     * @return Response
     */
    protected function showForm(string $server, string $database = ''): Response
    {
        $importOptions = $this->dbAdmin->getImportOptions($server, $database);

        // Make data available to views
        $this->view()->shareValues($importOptions);

        // Set main menu buttons
        $content = isset($importOptions['mainActions']) ?
            $this->uiBuilder->mainActions($importOptions['mainActions']) : '';
        $this->response->html($this->package->getMainActionsId(), $content);

        $formId = 'adminer-import-form';
        $webFileBtnId = 'adminer-import-web-file-btn';
        $sqlFilesBtnId = 'adminer-import-sql-files-btn';
        $sqlChooseBtnId = 'adminer-import-choose-files-btn';
        $sqlFilesDivId = 'adminer-import-sql-files-wrapper';
        $sqlFilesInputId = 'adminer-import-sql-files-input';
        $htmlIds = compact('formId', 'sqlFilesBtnId', 'sqlChooseBtnId', 'webFileBtnId', 'sqlFilesDivId', 'sqlFilesInputId');
        $content = $this->uiBuilder->importPage($htmlIds, $importOptions['contents'], $importOptions['labels']);

        $this->response->html($this->package->getDbContentId(), $content);
        $this->response->script("jaxon.dbadmin.setFileUpload('#$sqlFilesDivId', '#$sqlChooseBtnId', '#$sqlFilesInputId')");

        $this->jq("#$webFileBtnId")->click($this->rq()->executeWebFile($server, $database));
        $this->jq("#$sqlFilesBtnId")
            ->click($this->rq()->executeSqlFiles($server, $database, \pm()->form($formId)));

        return $this->response;
    }

    /**
     * Show the import form for a server
     *
     * @after('call' => 'showBreadcrumbs')
     * @after('call' => 'selectMenuItem', 'with' => ['#adminer-menu-action-server-import', 'adminer-server-actions'])
     *
     * @param string $server      The database server
     *
     * @return Response
     */
    public function showServerForm(string $server): Response
    {
        return $this->showForm($server, '');
    }

    /**
     * Show the import form for a database
     *
     * @after('call' => 'showBreadcrumbs')
     * @after('call' => 'selectMenuItem', 'with' => ['#adminer-menu-action-database-import', 'adminer-database-actions'])
     *
     * @param string $server      The database server
     * @param string $database    The database name
     *
     * @return Response
     */
    public function showDatabaseForm(string $server, string $database = ''): Response
    {
        return $this->showForm($server, $database);
    }

    /**
     * Run a webfile
     *
     * @param string $server      The database server
     * @param string $database    The database name
     *
     * @return Response
     */
    public function executeWebFile(string $server, string $database): Response
    {
        return $this->response;
    }

    /**
     * Run a webfile
     *
     * @upload('field' => 'adminer-import-sql-files-input')
     *
     * @param string $server      The database server
     * @param string $database    The database name
     * @param array $formValues
     *
     * @return Response
     */
    public function executeSqlFiles(string $server, string $database, array $formValues): Response
    {
        $files = \array_map(function($file) {
            return $file->path();
        }, $this->files()['sql_files']);
        $errorStops = $formValues['error_stops'] ?? false;
        $onlyErrors = $formValues['only_errors'] ?? false;

        if(!$files)
        {
            $this->response->dialog->error('No file uploaded!', 'Error');
            return $this->response;
        }

        $queryResults = $this->dbAdmin->executeSqlFiles($server,
            $files, $errorStops, $onlyErrors, $database);
        // $this->logger()->debug(\json_encode($queryResults));

        $content = $this->uiBuilder->queryResults($queryResults['results']);
        $this->response->html('adminer-command-results', $content);

        return $this->response;
    }
}
