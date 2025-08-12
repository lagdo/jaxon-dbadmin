<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Command;

use Lagdo\DbAdmin\Ajax\App\Page\PageActions;
use Lagdo\DbAdmin\Ui\Command\ImportUiBuilder;

use function array_map;
use function compact;
use function Jaxon\je;

trait ImportTrait
{
    /**
     * @var ImportUiBuilder
     */
    protected ImportUiBuilder $importUi;

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

        $formId = 'dbadmin-import-form';
        $webFileBtnId = 'dbadmin-import-web-file-btn';
        $sqlFilesBtnId = 'dbadmin-import-sql-files-btn';
        $sqlChooseBtnId = 'dbadmin-import-choose-files-btn';
        $sqlFilesDivId = 'dbadmin-import-sql-files-wrapper';
        $sqlFilesInputId = 'dbadmin-import-sql-files-input';
        $htmlIds = compact('formId', 'sqlFilesBtnId', 'sqlChooseBtnId', 'webFileBtnId', 'sqlFilesDivId', 'sqlFilesInputId');
        return $this->importUi->page($htmlIds, $importOptions['contents'], $importOptions['labels']);
    }

    /**
     * Called after rendering the component.
     *
     * @return void
     */
    protected function after(): void
    {
        $formId = 'dbadmin-import-form';
        $webFileBtnId = 'dbadmin-import-web-file-btn';
        $sqlFilesBtnId = 'dbadmin-import-sql-files-btn';
        $sqlChooseBtnId = 'dbadmin-import-choose-files-btn';
        $sqlFilesDivId = 'dbadmin-import-sql-files-wrapper';
        $sqlFilesInputId = 'dbadmin-import-sql-files-input';
        $this->response->jo('jaxon.dbadmin')->setFileUpload("#$sqlFilesDivId", "#$sqlChooseBtnId", "#$sqlFilesInputId");

        $this->response->jq("#$webFileBtnId")->click($this->rq()->executeWebFile($this->database));
        $this->response->jq("#$sqlFilesBtnId")->click($this->rq()->executeSqlFiles($this->database, je($formId)->rd()->form()));
    }

    /**
     * Run a webfile
     * @before notYetAvailable
     *
     * @param string $database    The database name
     *
     * @return void
     */
    public function executeWebFile(string $database): void
    {
    }

    /**
     * Run a webfile
     * @before notYetAvailable
     *
     * @upload dbadmin-import-sql-files-input
     *
     * @param string $database    The database name
     * @param array $formValues
     *
     * @return void
     */
    public function executeSqlFiles(string $database, array $formValues): void
    {
        // Set the current database, but do not update the databag.
        $this->db()->setCurrentDbName($database);

        if(!($files = $this->files()['sql_files'] ?? []))
        {
            $this->alert()->title('Error')->error('No file uploaded!');
            return;
        }

        $paths = array_map(fn($file) => $file->path(), $files);
        $errorStops = $formValues['error_stops'] ?? false;
        $onlyErrors = $formValues['only_errors'] ?? false;

        $queryResults = $this->db()->executeSqlFiles($paths, $errorStops, $onlyErrors);

        $content = $this->importUi->results($queryResults['results']);
        $this->response->html('dbadmin-command-results', $content);
    }
}
