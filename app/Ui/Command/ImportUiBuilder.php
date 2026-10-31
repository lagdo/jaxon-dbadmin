<?php

namespace Lagdo\DbAdmin\App\Ui\Command;

use Jaxon\Script\Call\JxnCall;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Command\Query;
use Lagdo\DbAdmin\Support\Translator;
use Lagdo\DbAdmin\App\Ui\Tab\Tab;
use Lagdo\UiBuilder\BuilderInterface;

use function Jaxon\jq;
use function Jaxon\pm;

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
    private function uploadFormId(): string
    {
        return $this->tab()->app()->id('dbadmin-import-upload-form');
    }

    /**
     * @return string
     */
    private function storageFormId(): string
    {
        return $this->tab()->app()->id('dbadmin-import-storage-form');
    }

    /**
     * @return string
     */
    public function filesDivId(): string
    {
        return $this->tab()->app()->id('dbadmin-import-sql-files-wrapper');
    }

    /**
     * @return mixed
     */
    private function importOptions(): mixed
    {
        return $this->ui->list(
            $this->ui->div(
                $this->ui->inputGroup(
                    $this->ui->label(
                        $this->ui->text($this->trans->lang('Stop on error'))
                    ),
                    $this->ui->checkbox()
                        ->setName('error_stops')
                )
            ),
            $this->ui->div(
                $this->ui->inputGroup(
                    $this->ui->label(
                        $this->ui->text($this->trans->lang('Show only errors'))
                    ),
                    $this->ui->checkbox()
                        ->setName('only_errors')
                )
            )
        );
    }

    /**
     * @param array $contents
     * @param JxnCall $handler
     *
     * @return mixed
     */
    private function uploadCol(array $contents, JxnCall $handler): mixed
    {
        $fileInputId = $this->tab()->app()->id('dbadmin-import-sql-files-input');
        $formId = $this->uploadFormId();
        return $this->ui->col(
            $this->ui->form(
                $this->ui->row(
                    $this->ui->col(
                        $this->ui->label($this->trans->lang('File upload'))
                    )->unit(1, 3),
                    $this->ui->when(isset($contents['upload']), fn() =>
                        $this->ui->col($this->ui->html($contents['upload']))
                            ->unit(2, 3)
                    ),
                    $this->ui->when(!isset($contents['upload']), fn() =>
                        $this->ui->col($this->ui->html($contents['upload_disabled']))
                            ->unit(2, 3)
                    )
                ),
                $this->ui->row(
                    $this->ui->when(isset($contents['upload']), fn() =>
                        $this->ui->col(
                            $this->ui->div(
                                $this->ui->input()
                                    ->setType('file')
                                    ->setName('sql_files[]')
                                    ->setId($fileInputId)
                                    ->setMultiple('multiple'),
                            )->setStyle('display:none;'),
                            $this->ui->inputGroup(
                                $this->ui->button($this->ui->html($this->trans->lang('Select') . '&hellip;'))
                                    ->primary()
                                    // Trigger a click on the hidden file select component when the user clicks on the button.
                                    ->jxnClick(jq("#$fileInputId")->trigger('click')),
                                $this->ui->input()
                                    ->setType('text')
                                    ->setReadonly('readonly')
                            )
                        )->unit(1, 1)
                            ->setId($this->filesDivId())
                    )
                ),
                $this->ui->div(
                    $this->ui->div(
                        $this->ui->button($this->ui->text($this->trans->lang('Execute')))
                            ->primary()
                            ->setStyle('padding-left: 30px; padding-right: 30px;')
                            ->jxnClick($handler->importFromUpload(pm()->form($formId)))
                    ),
                    $this->ui->div()
                        ->setStyle('flex: 1;')
                        ->tbnBindApp(rq(Query\ImportDuration::class), 'upload'),
                    $this->importOptions()
                )->setStyle('display: flex; flex-direction: row; column-gap: 10px; margin-top: 10px;')
            )->setId($formId)
        )->setStyle('padding-right: 10px;');
    }

    /**
     * @param array $contents
     * @param JxnCall $handler
     *
     * @return mixed
     */
    private function storageCol(array $contents, JxnCall $handler): mixed
    {
        $formId = $this->storageFormId();
        return $this->ui->col(
            $this->ui->form(
                $this->ui->row(
                    $this->ui->col(
                        $this->ui->label($this->trans->lang('From server'))
                    )->unit(1, 3),
                    $this->ui->col(
                        $this->ui->span($this->ui->text($this->trans->lang('Webserver file %s', '')))
                    )->unit(2, 3)
                ),
                $this->ui->row(
                    $this->ui->col(
                        $this->ui->input()
                            ->setType('text')
                            ->setName('path')
                            ->setValue($contents['path'])
                            ->setReadonly('readonly')
                    )->unit(1, 1)
                ),
                $this->ui->div(
                    $this->ui->div(
                        $this->ui->button($this->ui->text($this->trans->lang('Run file')))
                            ->primary()
                            ->setStyle('padding-left: 30px; padding-right: 30px;')
                            ->jxnClick($handler->importFromStorage(pm()->form($formId)))
                    ),
                    $this->ui->div()
                        ->setStyle('flex: 1;')
                        ->tbnBindApp(rq(Query\ImportDuration::class), 'server'),
                    $this->importOptions()
                )->setStyle('display: flex; flex-direction: row; column-gap: 10px; margin-top: 10px;')
            )->setId($formId)
        )->setStyle('padding-left: 10px;');
    }

    /**
     * @param array $contents
     * @param JxnCall $handler
     *
     * @return string
     */
    public function import(array $contents, JxnCall $handler): string
    {
        return $this->ui->build(
            $this->ui->div()
                ->setStyle('margin-bottom: 10px;')
                ->setId($this->tab()->app()->id('dbadmin-command-details')),
            $this->ui->row(
                $this->uploadCol($contents, $handler)
                    ->unit(1, 2),
                $this->ui->when(isset($contents['path']), fn() =>
                    $this->storageCol($contents, $handler)
                        ->unit(1, 2)
                )
            ),
            $this->ui->div()
                ->setStyle('margin-tom: 10px;')
                ->tbnBindApp(rq(Query\ImportResult::class))
        );
    }
}
