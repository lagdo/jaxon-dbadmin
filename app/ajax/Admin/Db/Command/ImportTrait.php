<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Command;

use Jaxon\Attributes\Attribute\Before;
use Jaxon\Attributes\Attribute\Callback;
use Jaxon\Attributes\Attribute\Upload;
use Lagdo\DbAdmin\Ajax\Admin\Page\PageActions;
use Lagdo\DbAdmin\Ui\Command\ImportUiBuilder;

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
        $handlers = [
            'webFileBtn' => $this->rq()->executeWebFile(),
            'sqlFilesBtn' => $this->rq()->executeSqlFiles($this->importUi->formValues()),
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
        $this->response()->jo('jaxon.dbadmin')->setFileUpload($this->importUi->filesDivId());
    }

    /**
     * Run a webfile
     *
     * @return void
     */
    #[Before('notYetAvailable')]
    public function executeWebFile(): void
    {}

    /**
     * Run a webfile
     *
     * @param array $formValues
     *
     * @return void
     */
    #[Callback('jaxon.dbadmin.upload')]
    #[Upload('dbadmin-import-sql-files-input')]
    public function executeSqlFiles(array $formValues): void
    {
        if(!($files = $this->files()['sql_files'] ?? []))
        {
            $this->alert()->title('Error')->error('No file uploaded!');
            return;
        }

        $errorStops = $formValues['error_stops'] ?? false;
        $onlyErrors = $formValues['only_errors'] ?? false;
        $results = $this->db()->executeSqlFiles($files, $errorStops, $onlyErrors);

        $this->cl(Query\ResultSet::class)->results($results)->render();
    }
}
