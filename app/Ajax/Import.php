<?php

namespace Lagdo\DbAdmin\App\Ajax;

use Jaxon\Response\Response;
use Lagdo\DbAdmin\App\CallableClass;

use Exception;

use function compact;
use function Jaxon\pm;

/**
 * @databag selection
 */
class Import extends CallableClass
{
    /**
     * Show the import form
     *
     * @param string $database    The database name
     *
     * @return Response
     */
    protected function showForm(string $database = ''): Response
    {
        [$server,] = $this->bag('selection')->get('db');
        $importOptions = $this->db->getImportOptions($server, $database);

        // Make data available to views
        $this->view()->shareValues($importOptions);

        // Set main menu buttons
        $content = isset($importOptions['mainActions']) ?
            $this->ui->mainActions($importOptions['mainActions']) : '';
        $this->response->html($this->package->getMainActionsId(), $content);

        $formId = 'adminer-import-form';
        $webFileBtnId = 'adminer-import-web-file-btn';
        $sqlFilesBtnId = 'adminer-import-sql-files-btn';
        $sqlChooseBtnId = 'adminer-import-choose-files-btn';
        $sqlFilesDivId = 'adminer-import-sql-files-wrapper';
        $sqlFilesInputId = 'adminer-import-sql-files-input';
        $htmlIds = compact('formId', 'sqlFilesBtnId', 'sqlChooseBtnId', 'webFileBtnId', 'sqlFilesDivId', 'sqlFilesInputId');
        $content = $this->ui->importPage($htmlIds, $importOptions['contents'], $importOptions['labels']);

        $this->response->html($this->package->getDbContentId(), $content);
        $this->response->script("jaxon.dbadmin.setFileUpload('#$sqlFilesDivId', '#$sqlChooseBtnId', '#$sqlFilesInputId')");

        $this->jq("#$webFileBtnId")->click($this->rq()->executeWebFile($database));
        $this->jq("#$sqlFilesBtnId")->click($this->rq()->executeSqlFiles($database, pm()->form($formId)));

        return $this->response;
    }

    /**
     * Show the import form for a server
     *
     * @after('call' => 'showBreadcrumbs')
     * @after('call' => 'selectMenuItem', 'with' => ['#adminer-menu-action-server-import', 'adminer-server-actions'])
     *
     * @return Response
     */
    public function showServerForm(): Response
    {
        return $this->showForm();
    }

    /**
     * Show the import form for a database
     *
     * @after('call' => 'showBreadcrumbs')
     * @after('call' => 'selectMenuItem', 'with' => ['#adminer-menu-action-database-import', 'adminer-database-actions'])
     *
     * @param string $database    The database name
     *
     * @return Response
     */
    public function showDatabaseForm(string $database = ''): Response
    {
        return $this->showForm($database);
    }

    /**
     * Run a webfile
     *
     * @param string $database    The database name
     *
     * @return Response
     */
    public function executeWebFile(string $database): Response
    {
        return $this->response;
    }

    /**
     * Run a webfile
     *
     * @upload('field' => 'adminer-import-sql-files-input')
     *
     * @param string $database    The database name
     * @param array $formValues
     *
     * @return Response
     */
    public function executeSqlFiles(string $database, array $formValues): Response
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

        [$server,] = $this->bag('selection')->get('db');
        $queryResults = $this->db->executeSqlFiles($server,
            $files, $errorStops, $onlyErrors, $database);

        $content = $this->ui->queryResults($queryResults['results']);
        $this->response->html('adminer-command-results', $content);

        return $this->response;
    }
}
