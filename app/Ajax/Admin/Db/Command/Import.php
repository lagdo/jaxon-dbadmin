<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Command;

use Lagdo\DbAdmin\App\Ajax\Admin\Db\Database\FuncComponent;
use Lagdo\DbAdmin\App\Ui\Command\ImportUiBuilder;
use Lagdo\DbAdmin\Support\Service\Import\StatusService;

class Import extends FuncComponent
{
    /**
     * @param ImportUiBuilder $importUi
     * @param StatusService $statusService
     */
    public function __construct(private ImportUiBuilder $importUi,
        private StatusService $statusService)
    {}

    /**
     * @return void
     */
    public function showStorages(): void
    {
        $storages = $this->statusService->getStorageLabels();
        $title = $this->trans()->lang('Select a storage');
        $content = $this->importUi->selectStorageForm($storages);
        $buttons = [[
            'title' => 'Cancel',
            'class' => 'danger',
            'click' => 'close',
        ],[
            'title' => 'Select',
            'class' => 'primary',
            'click' => $this->rq()
                ->showDumps($this->importUi->storageSelectValue()),
        ]];
        $this->modal()->show($title, $content, $buttons);
    }

    /**
     * @param string $storage
     *
     * @return void
     */
    public function showDumps(string $storage): void
    {
        if ($storage === '') {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error('Please select a storage.');
            return;
        }

        $dumps = $this->statusService->getDumps($storage);
        if ($dumps === null) {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error('Unable to find the selected storage.');
            return;
        }

        $this->modal()->hide();

        $title = $this->trans()->lang('Select a database dump');
        $content = $this->importUi->selectDumpForm($dumps);
        $buttons = [[
            'title' => 'Cancel',
            'class' => 'danger',
            'click' => 'close',
        ],[
            'title' => 'Select',
            'class' => 'primary',
            'click' => $this->rq()
                ->selectDump($storage, $this->importUi->dumpSelectValue()),
        ]];
        $this->modal()->show($title, $content, $buttons);
    }

    /**
     * @param string $storage
     * @param string $dump
     *
     * @return void
     */
    public function selectDump(string $storage, string $dump): void
    {
        $dump = $this->statusService->getDump($storage, $dump);
        if ($dump === null) {
            $this->alert()
                ->title($this->trans()->lang('Error'))
                ->error('Unable to find the selected dump.');
            return;
        }

        $this->modal()->hide();

        $this->response()->jq('#' . $this->importUi->storageInputId())
            ->attr('value', $dump->path())
            ->attr('data-storage', $storage)
            ->attr('title', $this->statusService->getDumpLabel($dump));
    }

    /**
     * @param string $importId
     * @param int $failures
     *
     * @return void
     */
    public function updateStatus(string $importId, int $failures = 0): void
    {
        $delay = 2000; // 2 seconds.
        $import = $this->statusService->readStatus("$importId.json");
        if ($import === null) {
            if ($failures++ < 5) {
                $this->response()->jo('jaxon.dbadmin')
                    ->scheduleImportStatusUpdate($importId, $failures, $delay);
            }
            return;
        }

        $this->cl(ImportStatus::class)
            ->set('import', $import)
            ->render();
        $this->cl(ImportMenuButton::class)
            ->set('import', $import)
            ->render();

        if ($import['status'] === 'preparing' || $import['status'] === 'running') {
            $this->response()->jo('jaxon.dbadmin')
                ->scheduleImportStatusUpdate($importId, 0, $delay);
        }
    }
}
