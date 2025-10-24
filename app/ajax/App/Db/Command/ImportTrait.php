<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Command;

use Jaxon\Attributes\Attribute\Before;
use Jaxon\Attributes\Attribute\Upload;
use Lagdo\DbAdmin\Ajax\App\Page\PageActions;
use Lagdo\DbAdmin\Ui\Command\ImportUiBuilder;

use function array_map;
use function Jaxon\je;

trait ImportTrait
{
    /**
     * @var ImportUiBuilder
     */
    protected ImportUiBuilder $importUi;

    /**
     * @return string
     */
    public function html(): string
    {
        $importOptions = $this->db()->getImportOptions();
        $formValues = je($this->importUi->formId)->rd()->form();
        $handlers = [
            'webFileBtn' => $this->rq()->executeWebFile(),
            'sqlFilesBtn' => $this->rq()->executeSqlFiles($formValues),
        ];

        // Set main menu buttons
        $this->cl(PageActions::class)->clear();

        return $this->importUi->import($importOptions['contents'], $handlers);
    }

    /**
     * @inheritDoc
     */
    protected function after(): void
    {
        $this->response->jo('jaxon.dbadmin')->setFileUpload("#{$this->importUi->sqlFilesDivId}");
    }

    /**
     * Run a webfile
     *
     * @return void
     */
    #[Before('notYetAvailable')]
    public function executeWebFile(): void
    {
    }

    /**
     * Run a webfile
     *
     * @param array $formValues
     *
     * @return void
     */
    #[Before('notYetAvailable')]
    #[Upload('dbadmin-import-sql-files-input')]
    public function executeSqlFiles(array $formValues): void
    {
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
