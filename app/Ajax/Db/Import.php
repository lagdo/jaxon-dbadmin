<?php

namespace Lagdo\DbAdmin\App\Ajax\Db;

use Jaxon\Response\Response;
use Lagdo\DbAdmin\App\CallableDbClass;

use function compact;
use function Jaxon\pm;

class Import extends CallableDbClass
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
        // Set the current database, but do not update the databag.
        $this->db->setCurrentDbName($database);

        $importOptions = $this->db->getImportOptions();

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
        $this->response->call("jaxon.dbadmin.setFileUpload", "#$sqlFilesDivId", "#$sqlChooseBtnId", "#$sqlFilesInputId");

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
     * @return Response
     */
    public function showDatabaseForm(): Response
    {
        [, $database] = $this->bag('dbadmin')->get('db');
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
        // Set the current database, but do not update the databag.
        $this->db->setCurrentDbName($database);

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

        $queryResults = $this->db->executeSqlFiles($files, $errorStops, $onlyErrors);

        $content = $this->ui->queryResults($queryResults['results']);
        $this->response->html('adminer-command-results', $content);

        return $this->response;
    }
}
