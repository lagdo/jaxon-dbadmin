<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Command;

use Jaxon\Attributes\Attribute\Before;
use Jaxon\Attributes\Attribute\Callback;
use Jaxon\Attributes\Attribute\Upload;
use Lagdo\DbAdmin\App\Ajax\Admin\Page\PageActions;
use Lagdo\DbAdmin\App\Ui\Command\ImportUiBuilder;
use Lagdo\DbAdmin\Support\Driver\UiDto\ExecOptions;

trait ImportTrait
{
    /**
     * @var ImportUiBuilder
     */
    protected ImportUiBuilder $importUi;

    /**
     * @return string
     */
    protected function content(): string
    {
        $importOptions = $this->db()->getImportOptions();
        $handlers = [
            'webFileBtn' => $this->rq()->executeWebFile(),
            'sqlFilesBtn' => $this->rq()->executeQueriesInFile($this->importUi->formValues()),
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
    public function executeQueriesInFile(array $formValues): void
    {
        if(!($files = $this->files()['sql_files'] ?? []))
        {
            $this->alert()->title('Error')->error('No file uploaded!');
            return;
        }

        $options = new ExecOptions($formValues['error_stops'] ?? false,
            $formValues['only_errors'] ?? false);
        $result = $this->db()->executeQueriesInFile($files[0], $options);

        $this->cl(Query\ImportResult::class)->set('result', $result)->render();
    }
}
