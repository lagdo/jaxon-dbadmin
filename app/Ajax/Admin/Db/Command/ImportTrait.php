<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Command;

use Jaxon\Attributes\Attribute\Callback;
use Jaxon\Attributes\Attribute\Inject;
use Jaxon\Attributes\Attribute\Upload;
use Jaxon\Request\Upload\FileInterface;
use Lagdo\DbAdmin\App\Ajax\Admin\Page\PageActions;
use Lagdo\DbAdmin\App\Ui\Command\ImportUiBuilder;
use Lagdo\DbAdmin\Support\Driver\DriverProxy;
use Lagdo\DbAdmin\Support\Service\Import\StatusService;

trait ImportTrait
{
    /**
     * @var ImportUiBuilder
     */
    #[Inject]
    private ImportUiBuilder $importUi;

    /**
     * @var StatusService
     */
    #[Inject]
    private StatusService $statusService;

    /**
     * @return DriverProxy
     */
    abstract protected function driver(): DriverProxy;

    /**
     * Get the uploaded files
     *
     * @return array<string, array<FileInterface>>
     */
    abstract protected function files(): array;

    /**
     * @return string
     */
    abstract protected function section(): string;

    /**
     * @return string
     */
    protected function content(): string
    {
        // Set main menu buttons
        $this->cl(PageActions::class)->clear();

        $contents = $this->driver()->getImportOptions()['contents'];
        return $this->importUi->import($contents, $this->rq(), $this->section());
    }

    /**
     * @inheritDoc
     */
    protected function after(): void
    {
        $this->response()->jo('jaxon.dbadmin')
            ->setFileUpload($this->importUi->uploadWrapperId());
    }

    /**
     * @param array $file       The database dump storage options
     * @param array $inputs    The import inputs
     *
     * @return void
     */
    private function startImport(array $file, array $inputs): void
    {
        // Create the import status file.
        $status = $this->statusService->initStatus($file, $inputs);
        if ($status === null) {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error('Unable to create the import status file.');
            return;
        }

        [$json, $importUrl] = $status;
        $this->response()->jo('jaxon.dbadmin')->downloadFile($importUrl);

        $updateImportStatusFunc = $this->section() . 'ImportStatus';
        $this->response()->jo('jaxon.dbadmin')
            ->$updateImportStatusFunc($json, 0, 200);
    }

    /**
     * @param array $inputs
     *
     * @return void
     */
    #[Callback('jaxon.dbadmin.upload')]
    #[Upload('dbadmin-import-upload-input')]
    public function importFromUpload(array $inputs): void
    {
        if ($this->statusService->statusStorage() === null) {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error('The export feature is not setup.');
            return;
        }
        $fileOptions = $this->statusService->uploadStorageOptions();
        if ($fileOptions === null) {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error('The import feature is not setup.');
            return;
        }
        if (!($file = $this->files()['dump'][0] ?? null)) {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error('No file uploaded!');
            return;
        }

        $fileOptions['path'] = $file->path();
        $this->startImport($fileOptions, $inputs);
    }

    /**
     * @param string $storage
     * @param string $path
     * @param array $inputs
     *
     * @return void
     */
    public function importFromStorage(string $storage, string $path, array $inputs): void
    {
        if ($this->statusService->statusStorage() === null) {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error('The export feature is not setup.');
            return;
        }
        $fileOptions = $this->statusService->getDumpStorageOptions($storage);
        if ($fileOptions === null) {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error('Unable to find the selected storage.');
            return;
        }
        if ($this->statusService->getDump($storage, $path) === null) {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error('Unable to find the selected dump.');
            return;
        }

        $fileOptions['path'] = $path;
        $this->startImport($fileOptions, $inputs);
    }

    /**
     * @param string $importId
     * @param int $failures
     *
     * @return void
     */
    public function updateStatus(string $importId, int $failures = 0): void
    {
        $updateImportStatusFunc = $this->section() . 'ImportStatus';
        $delay = 2000; // 2 seconds.
        $import = $this->statusService->readStatus("$importId.json");
        if ($import === null) {
            if ($failures++ < 5) {
                $this->response()->jo('jaxon.dbadmin')
                    ->$updateImportStatusFunc($importId, $failures, $delay);
            }
            return;
        }

        $this->cl(ImportStatus::class)
            ->set('import', $import)
            ->render();
        $this->cl(ImportMenuButton::class)
            ->set('import', $import)
            ->render();

        $status = $import['status'];
        if ($status === 'preparing' || $status === 'running') {
            $this->response()->jo('jaxon.dbadmin')
                ->$updateImportStatusFunc($importId, 0, $delay);
        }
    }
}
