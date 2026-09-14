<?php

namespace Lagdo\DbAdmin\App\Ui\Command;

use Jaxon\Script\JsExpr;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Command\Query;
use Lagdo\DbAdmin\Support\Translator;
use Lagdo\DbAdmin\App\Ui\Tab\Tab;
use Lagdo\UiBuilder\BuilderInterface;

use function Jaxon\form;
use function Jaxon\jq;

class ImportUiBuilder
{
    use QueryResultTrait;

    /**
     * @param Translator $trans
     * @param BuilderInterface $ui
     * @param Tab $tab
     */
    public function __construct(protected Translator $trans,
        protected BuilderInterface $ui, protected Tab $tab)
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
    private function formId(): string
    {
        return $this->tab()->app()->id('dbadmin-import-form');
    }

    /**
     * @return string
     */
    public function filesDivId(): string
    {
        return $this->tab()->app()->id('dbadmin-import-sql-files-wrapper');
    }

    /**
     * @return array
     */
    public function formValues(): array
    {
        return form($this->formId());
    }

    /**
     * @param array $contents
     * @param JsExpr $handler
     *
     * @return mixed
     */
    private function fileCol(array $contents, JsExpr $handler): mixed
    {
        $sqlFilesInputId = $this->tab()->app()->id('dbadmin-import-sql-files-input');
        return $this->ui->col(
            $this->ui->row(
                $this->ui->col(
                    $this->ui->label($this->trans->lang('File upload'))
                )
                ->unit(1, 3),
                $this->ui->when(isset($contents['upload']), fn() =>
                    $this->ui->col($this->ui->html($contents['upload']))
                        ->unit(2, 3)
                ),
                $this->ui->when(!isset($contents['upload']), fn() =>
                    $this->ui->col($this->ui->html($contents['upload_disabled']))
                        ->unit(2, 3)
                ),
            ),
            $this->ui->row(
                $this->ui->when(isset($contents['upload']), fn() =>
                    $this->ui->col(
                        $this->ui->inputGroup(
                            $this->ui->button($this->ui->html($this->trans->lang('Select') . '&hellip;'))
                                ->primary()
                                // Trigger a click on the hidden file select component when the user clicks on the button.
                                ->jxnClick(jq("#$sqlFilesInputId")->trigger('click')),
                            $this->ui->input()
                                ->setType('file')->setName('sql_files[]')
                                ->setId($sqlFilesInputId)
                                ->setMultiple('multiple')
                                ->setStyle('display:none;'),
                            $this->ui->input()
                                ->setType('text')->setReadonly('readonly')
                        )->setId($this->filesDivId())
                    )->unit(1, 1)
                )
            ),
            $this->ui->row(
                $this->ui->col(
                    $this->ui->button($this->ui->text($this->trans->lang('Execute')))
                        ->fullWidth()->primary()
                        ->jxnClick($handler)
                )->unit(1, 3),
                $this->ui->col()
                    ->unit(1, 3)
                    ->tbnBindApp(rq(Query\ImportDuration::class), 'upload')
            ),
        );
    }

    /**
     * @param array $contents
     * @param JsExpr $handler
     *
     * @return mixed
     */
    private function pathCol(array $contents, JsExpr $handler): mixed
    {
        return $this->ui->col(
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
                        ->setValue($contents['path'])
                        ->setReadonly('readonly')
                )->unit(1, 1)
            ),
            $this->ui->row(
                $this->ui->col(
                    $this->ui->button($this->ui->text($this->trans->lang('Run file')))
                        ->fullWidth()->primary()
                        ->jxnClick($handler)
                )->unit(1, 3),
                $this->ui->col()
                    ->unit(1, 3)
                    ->tbnBindApp(rq(Query\ImportDuration::class), 'server')
            ),
        );
    }

    /**
     * @return mixed
     */
    private function optionsCol(): mixed
    {
        return $this->ui->col(
            $this->ui->row(
                $this->ui->col(
                    // Actually an offset. TODO: a parameter for that.
                    $this->ui->html('&nbsp;')
                )->unit(1, 4),
                $this->ui->col(
                    $this->ui->inputGroup(
                        $this->ui->label(
                            $this->ui->text($this->trans->lang('Stop on error'))
                        ),
                        $this->ui->checkbox()
                            ->setName('error_stops')
                    )
                )->unit(1, 4),
                $this->ui->col(
                    $this->ui->inputGroup(
                        $this->ui->label(
                            $this->ui->text($this->trans->lang('Show only errors'))
                        ),
                        $this->ui->checkbox()
                            ->setName('only_errors')
                    )
                )->unit(1, 4),
            )
        );
    }

    /**
     * @param array $contents
     * @param array<JsExpr> $handlers
     *
     * @return string
     */
    public function import(array $contents, array $handlers): string
    {
        return $this->ui->build(
            $this->ui->row(
                $this->ui->col()
                    ->unit(1, 1)
                    ->setId($this->tab()->app()->id('dbadmin-command-details')),
                $this->ui->col(
                    $this->ui->form(
                        $this->ui->row(
                            $this->fileCol($contents, $handlers['sqlFilesBtn'])
                                ->unit(1, 2),
                            $this->ui->when(isset($contents['path']), fn() =>
                                $this->pathCol($contents, $handlers['webFileBtn'])
                                    ->unit(1, 2)
                            ),
                        ),
                        $this->ui->row(
                            $this->optionsCol()->unit(1, 1)
                        )
                    )->setId($this->formId())
                )->unit(1, 1),
                $this->ui->col()
                    ->unit(1, 1)
                    ->tbnBindApp(rq(Query\ImportResult::class))
            )
        );
    }
}
