<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Command;

use Lagdo\DbAdmin\App\Ajax\Page\PageActions;

use function compact;
use function Jaxon\pm;

trait ImportTrait
{
    /**
     * @var string
     */
    private $database = '';

    /**
     * @return string
     */
    public function html(): string
    {
        // Set the current database, but do not update the databag.
        $this->db()->setCurrentDbName($this->database);

        $importOptions = $this->db()->getImportOptions();

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
        return $this->ui()->importPage($htmlIds, $importOptions['contents'], $importOptions['labels']);
    }

    /**
     * Called after rendering the component.
     *
     * @return void
     */
    protected function after()
    {
        $formId = 'adminer-import-form';
        $webFileBtnId = 'adminer-import-web-file-btn';
        $sqlFilesBtnId = 'adminer-import-sql-files-btn';
        $sqlChooseBtnId = 'adminer-import-choose-files-btn';
        $sqlFilesDivId = 'adminer-import-sql-files-wrapper';
        $sqlFilesInputId = 'adminer-import-sql-files-input';
        $this->response->js('jaxon.dbadmin')->setFileUpload("#$sqlFilesDivId", "#$sqlChooseBtnId", "#$sqlFilesInputId");

        $this->response->jq("#$webFileBtnId")->click($this->rq()->executeWebFile($this->database));
        $this->response->jq("#$sqlFilesBtnId")->click($this->rq()->executeSqlFiles($this->database, pm()->form($formId)));
    }

    /**
     * Run a webfile
     *
     * @param string $database    The database name
     *
     * @return void
     */
    public function executeWebFile(string $database)
    {
    }

    /**
     * Run a webfile
     *
     * upload('field' => 'adminer-import-sql-files-input')
     *
     * @param string $database    The database name
     * @param array $formValues
     *
     * @return void
     */
    public function executeSqlFiles(string $database, array $formValues)
    {
        // Set the current database, but do not update the databag.
        $this->db()->setCurrentDbName($database);

        $files = \array_map(function($file) {
            return $file->path();
        }, $this->files()['sql_files']);
        $errorStops = $formValues['error_stops'] ?? false;
        $onlyErrors = $formValues['only_errors'] ?? false;

        if(!$files)
        {
            $this->alert()->title('Error')->error('No file uploaded!');
            return;
        }

        $queryResults = $this->db()->executeSqlFiles($files, $errorStops, $onlyErrors);

        $content = $this->ui()->queryResults($queryResults['results']);
        $this->response->html('adminer-command-results', $content);
    }
}
