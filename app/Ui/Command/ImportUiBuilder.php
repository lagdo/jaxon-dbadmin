<?php

namespace Lagdo\DbAdmin\App\Ui\Command;

use Jaxon\Script\Call\JxnCall;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Command;
use Lagdo\DbAdmin\Support\Translator;
use Lagdo\DbAdmin\App\Ui\Tab\Tab;
use Lagdo\UiBuilder\BuilderInterface;
use League\Flysystem\Filesystem;

use function Jaxon\jq;
use function Jaxon\pm;
use function pathinfo;
use function sprintf;

class ImportUiBuilder
{
    use QueryResultTrait;

    /**
     * @param Translator $trans
     * @param BuilderInterface $ui
     * @param Tab $tab
     */
    public function __construct(private Translator $trans,
        private BuilderInterface $ui, private Tab $tab)
    {}

    /**
     * @return Tab
     */
    protected function tab(): Tab
    {
        return $this->tab;
    }

    /**
     * @return string
     */
    private function optionsFormId(): string
    {
        return $this->tab()->app()->id('dbadmin-import-options-form');
    }

    /**
     * @return mixed
     */
    private function optionsValues(): mixed
    {
        return pm()->form($this->optionsFormId());
    }

    /**
     * @return string
     */
    public function storageInputId(): string
    {
        return $this->tab()->app()->id('dbadmin-import-storage-input');
    }

    /**
     * @return string
     */
    public function uploadWrapperId(): string
    {
        return $this->tab()->app()->id('dbadmin-import-sql-files-wrapper');
    }

    /**
     * @return mixed
     */
    private function importOptionsForm(): mixed
    {
        return $this->ui->form(
            $this->ui->label($this->trans->lang('Import options')),
            $this->ui->div(
                $this->ui->div(
                    $this->ui->div(
                        $this->ui->inputGroup(
                            $this->ui->label(
                                $this->ui->text($this->trans->lang('Stop on error'))
                            ),
                            $this->ui->checkbox()->setName('error_stops')
                        )
                    ),
                    $this->ui->div(
                        $this->ui->inputGroup(
                            $this->ui->label(
                                $this->ui->text($this->trans->lang('Show only errors'))
                            ),
                            $this->ui->checkbox()->setName('only_errors')
                        )
                    )
                )->setStyle('display: flex; column-gap: 10px;'),
                $this->ui->div(
                    $this->ui->inputGroup(
                        $this->ui->label(
                            $this->ui->text('The .sql, .gz, .gzip, .bz2, .bzip2 extensions are accepted.')
                        )
                    )
                )
            )->setStyle('display: flex; justify-content: space-between;')
        )->setId($this->optionsFormId())
            ->setStyle('width: 100%;');
    }

    /**
     * @param array $contents
     * @param JxnCall $handler
     *
     * @return mixed
     */
    private function uploadForm(array $contents, JxnCall $handler): mixed
    {
        $uploadInputId = $this->tab()->app()->id('dbadmin-import-upload-input');
        $importHandler = $handler->importFromUpload($this->optionsValues())
            ->ifne(jq("#$uploadInputId")->val(), '')
            ->elseWarning($this->trans->lang('Please select a file'));
        return $this->ui->col(
            $this->ui->form(
                $this->ui->when(isset($contents['upload']), fn() =>
                    $this->ui->div(
                        $this->ui->formField(
                            $this->ui->inputGroup(
                                $this->ui->button($this->ui->html($this->trans->lang('Select') . '&hellip;'))
                                    ->primary()
                                    // Trigger a click on the hidden file select component when the user clicks on the button.
                                    ->jxnClick(jq("#$uploadInputId")->trigger('click')),
                                $this->ui->input()
                                    ->setType('text')
                                    ->setReadonly('readonly'),
                                $this->ui->button($this->ui->text($this->trans->lang('Execute')))
                                    ->primary()
                                    ->jxnClick($importHandler)
                            ) // ->help($contents['upload'] ?? $contents['upload_disabled'])
                        )->label($this->trans->lang('File upload')),
                        $this->ui->div(
                            $this->ui->input()
                                ->setType('file')
                                ->setName('dump')
                                ->setId($uploadInputId)
                        )->setStyle('display:none;'),
                    )->setId($this->uploadWrapperId())
                )
            )
        )->setStyle('padding-right: 10px;');
    }

    /**
     * @param JxnCall $handler
     *
     * @return mixed
     */
    private function storageForm(JxnCall $handler): mixed
    {
        $storageInputId = $this->storageInputId();
        $storageValue = jq("#$storageInputId")->attr('data-storage');
        $importFile = jq("#$storageInputId")->val();
        $importHandler = $handler->importFromStorage($storageValue,
            $importFile, $this->optionsValues())
                ->ifne($importFile, '')
                ->elseWarning($this->trans->lang('Please select a file'));
        return $this->ui->col(
            $this->ui->form(
                $this->ui->formField(
                    $this->ui->inputGroup(
                        $this->ui->button($this->trans->lang('Select') . '&hellip;')
                            ->primary()
                            ->jxnClick(rq(Command\DumpStorage::class)->showStorages()),
                        $this->ui->input()
                            ->setType('text')
                            ->setName('path')
                            ->setValue('')
                            ->setReadonly('readonly')
                            ->setId($this->storageInputId())
                            ->setDataStorage(''),
                        $this->ui->button($this->trans->lang('Execute'))
                            ->primary()
                            ->jxnClick($importHandler)
                    ) // ->help($this->trans->lang('Webserver file %s', ''))
                )->label($this->trans->lang('From storage'))
            )
        )->setStyle('padding-left: 10px;');
    }

    /**
     * @return string
     */
    private function storageSelectId(): string
    {
        return $this->tab()->app()->id('dbadmin-import-select-storage');
    }

    /**
     * @return mixed
     */
    public function storageSelectValue(): mixed
    {
        return pm()->select($this->storageSelectId());
    }

    /**
     * @return string
     */
    private function dumpSelectId(): string
    {
        return $this->tab()->app()->id('dbadmin-import-select-dump');
    }

    /**
     * @return mixed
     */
    public function dumpSelectValue(): mixed
    {
        return pm()->select($this->dumpSelectId());
    }

    /**
     * @param array<string, array<string|Filesystem>> $storages
     *
     * @return string
     */
    public function selectStorageForm(array $storages): string
    {
        return $this->ui->build(
            $this->ui->form(
                $this->ui->formField(
                    $this->ui->select(
                        $this->ui->option('')->selected(),
                        $this->ui->each($storages, fn(string $label, string $value) =>
                            $this->ui->option($label)->setValue($value))
                    )->setId($this->storageSelectId())
                )->label('Storage')
            )
        );
    }

    /**
     * @param array<string, string> $dumps
     *
     * @return string
     */
    public function selectDumpForm(array $dumps): string
    {
        return $this->ui->build(
            $this->ui->form(
                $this->ui->formField(
                    $this->ui->select(
                        $this->ui->each($dumps, fn(string $label, string $path) =>
                            $this->ui->option($label)->setValue($path))
                    )->setId($this->dumpSelectId())
                )->label('Database dump')
            )
        );
    }

    /**
     * @param array $contents
     * @param JxnCall $handler
     * @param string $section
     *
     * @return string
     */
    public function import(array $contents, JxnCall $handler, string $section): string
    {
        return $this->ui->build(
            $this->ui->div(
                $this->importOptionsForm()
            )->setStyle('display: flex; flex-direction: row; column-gap: 10px; margin-bottom: 15px;'),
            $this->ui->row(
                $this->uploadForm($contents, $handler)
                    ->unit(1, 2),
                $this->ui->when(isset($contents['path']), fn() =>
                    $this->storageForm($handler)
                        ->unit(1, 2)
                )
            ),
            $this->ui->div()
                ->setStyle('margin-tom: 10px;')
                ->tbnBindApp(rq(Command\Query\ImportResult::class), $section)
        );
    }

    /**
     * @param int $duration
     *
     * @return string
     */
    private function formatDuration(int $duration): string
    {
        $milliseconds = $duration % 1000;
        $seconds = (int)($duration / 1000);
        if ($seconds < 60) {
            return sprintf('%02d.%03d', $seconds, $milliseconds);
        }
        $minutes = (int)($seconds / 60);
        if ($minutes < 60) {
            return sprintf('%02d:%02d.%03d', $minutes, $seconds, $milliseconds);
        }
        $hours = (int)($minutes / 60);
        return sprintf('%02d:%02d:%02d.%03d', $hours, $minutes, $seconds, $milliseconds);
    }

    /**
     * @param array $import
     *
     * @return string
     */
    public function importStatus(array $import): string
    {
        $filename = pathinfo($import['file']['path'], PATHINFO_BASENAME);
        $status = $import['status'];
        $result = $import['result'] ?? '(undefined)';
        $count = $import['count'];
        $totalDuration = $this->formatDuration($import['duration']['total']);
        $queriesDuration = $this->formatDuration($import['duration']['queries']);

        return $this->ui->build(
            $this->ui->div(
                $this->ui->span("File: {$filename}."),
                $this->ui->html('<br/>'),
                $this->ui->span("Started at: {$import['date']['create']}."),
                $this->ui->html('<br/>'),
                $this->ui->when($status === 'preparing', fn() =>
                    $this->ui->list(
                        $this->ui->span("Status: {$status}. ")
                            ->setStyle('vertical-align: top;'),
                        $this->ui->progress()
                            ->setStyle('margin-left: 20px; height: 22px;')
                    )
                ),
                $this->ui->when($status === 'running' || $status === 'completed', fn() =>
                    $this->ui->list(
                        $this->ui->span("Total queries: {$count['total']}. " .
                            "Executed: {$count['runs']}. Errors: {$count['errors']}."),
                        $this->ui->html('<br/>'),
                        $this->ui->span("Total duration: {$totalDuration}. " .
                            "Query execution: {$queriesDuration}."),
                        $this->ui->html('<br/>'),
                        $this->ui->when($status === 'running', fn() =>
                            $this->ui->list(
                                $this->ui->span("Status: {$status}. ")
                                    ->setStyle('vertical-align: top;'),
                                $this->ui->progress()
                                    ->setMax($count['total'])
                                    ->setValue($count['runs'])
                                    ->setStyle('margin-left: 20px; height: 22px;')
                            )
                        ),
                        $this->ui->when($status === 'completed', fn() =>
                            $this->ui->html("Status: {$status}. Result: {$result}.")
                        )
                    )
                )
            )->setStyle('padding: 10px;')
        );
    }
}
