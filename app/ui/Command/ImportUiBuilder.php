<?php

namespace Lagdo\DbAdmin\Ui\Command;

use Jaxon\Script\JsExpr;
use Lagdo\DbAdmin\Ajax\Admin\Db\Command\Query;
use Lagdo\DbAdmin\Db\Translator;
use Lagdo\DbAdmin\Ui\TabApp;
use Lagdo\UiBuilder\BuilderInterface;

use function Jaxon\form;
use function Jaxon\jq;

class ImportUiBuilder
{
    use QueryResultsTrait;

    /**
     * @param Translator $trans
     * @param BuilderInterface $ui
     */
    public function __construct(protected Translator $trans, protected BuilderInterface $ui)
    {}

    /**
     * @return string
     */
    private function formId(): string
    {
        return TabApp::id('dbadmin-import-form');
    }

    /**
     * @return string
     */
    public function filesDivId(): string
    {
        return TabApp::id('dbadmin-import-sql-files-wrapper');
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
        $sqlFilesInputId = TabApp::id('dbadmin-import-sql-files-input');
        return $this->ui->col(
            $this->ui->row(
                $this->ui->col(
                    $this->ui->label($this->trans->lang('File upload'))
                )
                ->width(4),
                $this->ui->when(isset($contents['upload']), fn() =>
                    $this->ui->col($this->ui->html($contents['upload']))
                        ->width(8)
                ),
                $this->ui->when(!isset($contents['upload']), fn() =>
                    $this->ui->col($this->ui->html($contents['upload_disabled']))
                        ->width(8)
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
                        )
                        ->setId($this->filesDivId())
                    )
                    ->width(12)
                )
            ),
            $this->ui->row(
                $this->ui->col(
                    $this->ui->button($this->ui->text($this->trans->lang('Execute')))
                        ->fullWidth()->primary()
                        ->jxnClick($handler)
                )
                ->width(4)
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
                )
                ->width(4),
                $this->ui->col(
                    $this->ui->span($this->ui->text($this->trans->lang('Webserver file %s', '')))
                )
                ->width(8)
            ),
            $this->ui->row(
                $this->ui->col(
                    $this->ui->input()
                        ->setType('text')
                        ->setValue($contents['path'])
                        ->setReadonly('readonly')
                )
                ->width(12)
            ),
            $this->ui->row(
                $this->ui->col(
                    $this->ui->button($this->ui->text($this->trans->lang('Run file')))
                        ->fullWidth()->primary()
                        ->jxnClick($handler)
                )
                ->width(4)
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
                )->width(3),
                $this->ui->col(
                    $this->ui->inputGroup(
                        $this->ui->label(
                            $this->ui->text($this->trans->lang('Stop on error'))
                        ),
                        $this->ui->checkbox()
                            ->setName('error_stops')
                    )
                )
                ->width(3),
                $this->ui->col(
                    $this->ui->inputGroup(
                        $this->ui->label(
                            $this->ui->text($this->trans->lang('Show only errors'))
                        ),
                        $this->ui->checkbox()
                            ->setName('only_errors')
                    )
                )
                ->width(3),
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
                $this->ui->col()->width(12)->setId(TabApp::id('dbadmin-command-details')),
                $this->ui->col(
                    $this->ui->form(
                        $this->ui->row(
                            $this->fileCol($contents, $handlers['sqlFilesBtn'])
                                ->width(6),
                            $this->ui->when(isset($contents['path']), fn() =>
                                $this->pathCol($contents, $handlers['webFileBtn'])
                                    ->width(6)
                            ),
                        ),
                        $this->ui->row(
                            $this->optionsCol()->width(12)
                        )
                    )->wrapped(false)->setId($this->formId())
                )->width(12),
                $this->ui->col()
                    ->width(12)
                    ->tbnBindApp(rq(Query\Results::class))
            )
        );
    }
}
