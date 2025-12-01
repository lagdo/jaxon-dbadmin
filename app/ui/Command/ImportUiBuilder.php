<?php

namespace Lagdo\DbAdmin\Ui\Command;

use Jaxon\Script\JsExpr;
use Lagdo\DbAdmin\Ajax\App\Db\Command\Query;
use Lagdo\DbAdmin\Translator;
use Lagdo\UiBuilder\BuilderInterface;

use function Jaxon\jq;

class ImportUiBuilder
{
    use QueryResultsTrait;

    /**
     * @var string
     */
    public string $formId = 'dbadmin-import-form';

    /**
     * @var string
     */
    public string $sqlFilesDivId = 'dbadmin-import-sql-files-wrapper';

    /**
     * @param Translator $trans
     * @param BuilderInterface $ui
     */
    public function __construct(protected Translator $trans, protected BuilderInterface $ui)
    {}

    /**
     * @param array $contents
     * @param JsExpr $handler
     *
     * @return mixed
     */
    private function fileCol(array $contents, JsExpr $handler): mixed
    {
        $sqlFilesInputId = 'dbadmin-import-sql-files-input';
        return $this->ui->col(
            $this->ui->formRow(
                $this->ui->formCol(
                    $this->ui->label($this->trans->lang('File upload'))
                )
                ->width(4),
                $this->ui->when(isset($contents['upload']), fn() =>
                    $this->ui->formCol($this->ui->html($contents['upload']))
                        ->width(8)
                ),
                $this->ui->when(!isset($contents['upload']), fn() =>
                    $this->ui->formCol($this->ui->html($contents['upload_disabled']))
                        ->width(8)
                ),
            ),
            $this->ui->formRow(
                $this->ui->when(isset($contents['upload']), fn() =>
                    $this->ui->formCol(
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
                            $this->ui->formInput()
                                ->setType('text')->setReadonly('readonly')
                        )
                        ->setId($this->sqlFilesDivId)
                    )
                    ->width(12)
                )
            ),
            $this->ui->formRow(
                $this->ui->formCol(
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
            $this->ui->formRow(
                $this->ui->formCol(
                    $this->ui->label($this->trans->lang('From server'))
                )
                ->width(4),
                $this->ui->formCol(
                    $this->ui->span($this->ui->text($this->trans->lang('Webserver file %s', '')))
                )
                ->width(8)
            ),
            $this->ui->formRow(
                $this->ui->formCol(
                    $this->ui->formInput()
                        ->setType('text')
                        ->setValue($contents['path'])
                        ->setReadonly('readonly')
                )
                ->width(12)
            ),
            $this->ui->formRow(
                $this->ui->formCol(
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
            $this->ui->formRow(
                $this->ui->formCol(
                    // Actually an offset. TODO: a parameter for that.
                    $this->ui->html('&nbsp;')
                )->width(3),
                $this->ui->formCol(
                    $this->ui->inputGroup(
                        $this->ui->label(
                            $this->ui->text($this->trans->lang('Stop on error'))
                        ),
                        $this->ui->checkbox()
                            ->setName('error_stops')
                    )
                )
                ->width(3),
                $this->ui->formCol(
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
            $this->ui->col()->width(12)->setId('dbadmin-command-details'),
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
                )
                ->responsive(true)->wrapped(false)->setId($this->formId)
            )->width(12),
            $this->ui->col()
                ->width(12)
                ->jxnBind(rq(Query\Results::class)),
            $this->ui->col($this->ui->jxnHtml(rq(Query\Queries::class)))
                ->width(12)
                ->jxnBind(rq(Query\Queries::class))
        );
    }
}
