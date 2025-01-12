<?php

namespace Lagdo\DbAdmin\App\Ajax\Db;

use Lagdo\DbAdmin\App\CallableDbClass;
use Lagdo\DbAdmin\App\Ajax\Page\Content;
use Lagdo\DbAdmin\App\Ajax\Page\PageActions;

use function compact;
use function Jaxon\pm;

class Import extends CallableDbClass
{
    /**
     * Show the import form
     *
     * @param string $database    The database name
     *
     * @return void
     */
    protected function showForm(string $database = '')
    {
        // Set the current database, but do not update the databag.
        $this->db->setCurrentDbName($database);

        $importOptions = $this->db->getImportOptions();

        // Make data available to views
        $this->view()->shareValues($importOptions);

        // Set main menu buttons
        $this->cl(PageActions::class)->clear();

        $formId = 'adminer-import-form';
        $webFileBtnId = 'adminer-import-web-file-btn';
        $sqlFilesBtnId = 'adminer-import-sql-files-btn';
        $sqlChooseBtnId = 'adminer-import-choose-files-btn';
        $sqlFilesDivId = 'adminer-import-sql-files-wrapper';
        $sqlFilesInputId = 'adminer-import-sql-files-input';
        $htmlIds = compact('formId', 'sqlFilesBtnId', 'sqlChooseBtnId', 'webFileBtnId', 'sqlFilesDivId', 'sqlFilesInputId');
        $content = $this->ui->importPage($htmlIds, $importOptions['contents'], $importOptions['labels']);
        $this->cl(Content::class)->showHtml($content);

        $this->response->js('jaxon.dbadmin')->setFileUpload("#$sqlFilesDivId", "#$sqlChooseBtnId", "#$sqlFilesInputId");

        $this->response->jq("#$webFileBtnId")->click($this->rq()->executeWebFile($database));
        $this->response->jq("#$sqlFilesBtnId")->click($this->rq()->executeSqlFiles($database, pm()->form($formId)));
    }

    /**
     * Show the import form for a server
     *
     * @after('call' => 'showBreadcrumbs')
     * @after('call' => 'selectMenuItem', 'with' => ['#adminer-menu-action-server-import', 'adminer-server-actions'])
     *
     * @return void
     */
    public function showServerForm()
    {
        $this->showForm();
    }

    /**
     * Show the import form for a database
     *
     * @after('call' => 'showBreadcrumbs')
     * @after('call' => 'selectMenuItem', 'with' => ['#adminer-menu-action-database-import', 'adminer-database-actions'])
     *
     * @return void
     */
    public function showDatabaseForm()
    {
        [, $database] = $this->bag('dbadmin')->get('db');
        $this->showForm($database);
    }

    /**
     * Run a webfile
     *
     * @param string $database    The database name
     *
     * @return void
     */
    public function executeWebFile(string $database)
    {}

    /**
     * Run a webfile
     *
     * @upload('field' => 'adminer-import-sql-files-input')
     *
     * @param string $database    The database name
     * @param array $formValues
     *
     * @return void
     */
    public function executeSqlFiles(string $database, array $formValues)
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
            return;
        }

        $queryResults = $this->db->executeSqlFiles($files, $errorStops, $onlyErrors);

        $content = $this->ui->queryResults($queryResults['results']);
        $this->response->html('adminer-command-results', $content);
    }
}
