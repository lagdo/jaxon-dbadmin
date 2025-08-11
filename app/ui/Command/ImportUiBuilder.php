<?php

namespace Lagdo\DbAdmin\Ui\Command;

use Lagdo\DbAdmin\Translator;
use Lagdo\UiBuilder\BuilderInterface;

class ImportUiBuilder
{
    /**
     * @param Translator $trans
     * @param BuilderInterface $ui
     */
    public function __construct(protected Translator $trans, protected BuilderInterface $ui)
    {}

    /**
     * @param array $htmlIds
     * @param array $contents
     * @param array $labels
     *
     * @return mixed
     */
    private function fileCol(array $htmlIds, array $contents, array $labels): mixed
    {
        return $this->ui->col(
            $this->ui->formRow(
                $this->ui->formCol(
                    $this->ui->label($labels['file_upload'])
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
                            $this->ui->button($this->ui->html($labels['select'] . '&hellip;'))
                                ->primary()
                                ->setId($htmlIds['sqlChooseBtnId']),
                            $this->ui->input()
                                ->setType('file')->setName('sql_files[]')
                                ->setId($htmlIds['sqlFilesInputId'])
                                ->setMultiple('multiple')
                                ->setStyle('display:none;'),
                            $this->ui->formInput()
                                ->setType('text')->setReadonly('readonly')
                        )
                        ->setId($htmlIds['sqlFilesDivId'])
                    )
                    ->width(12)
                )
            ),
            $this->ui->formRow(
                $this->ui->formCol(
                    $this->ui->button($this->ui->text($labels['execute']))
                        ->fullWidth()->primary()
                        ->setId($htmlIds['sqlFilesBtnId'])
                )
                ->width(4)
            ),
        );
    }

    /**
     * @param array $htmlIds
     * @param array $contents
     * @param array $labels
     *
     * @return mixed
     */
    private function pathCol(array $htmlIds, array $contents, array $labels): mixed
    {
        return $this->ui->col(
            $this->ui->formRow(
                $this->ui->formCol(
                    $this->ui->label($labels['from_server'])
                )
                ->width(4),
                $this->ui->formCol(
                    $this->ui->span($this->ui->text($labels['path']))
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
                    $this->ui->button($this->ui->text($labels['run_file']))
                        ->fullWidth()->primary()
                        ->setId($htmlIds['webFileBtnId'])
                )
                ->width(4)
            ),
        );
    }

    /**
     * @param array $labels
     *
     * @return mixed
     */
    private function optionsCol(array $labels): mixed
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
                            $this->ui->text($labels['error_stops'])
                        ),
                        $this->ui->checkbox()
                            ->setName('error_stops')
                    )
                )
                ->width(3),
                $this->ui->formCol(
                    $this->ui->inputGroup(
                        $this->ui->label(
                            $this->ui->text($labels['only_errors'])
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
     * @param array $htmlIds
     * @param array $contents
     * @param array $labels
     *
     * @return string
     */
    public function page(array $htmlIds, array $contents, array $labels): string
    {
        return $this->ui->build(
            $this->ui->col()->width(12)->setId('dbadmin-command-details'),
            $this->ui->col(
                $this->ui->form(
                    $this->ui->row(
                        $this->fileCol($htmlIds, $contents, $labels)->width(6),
                        $this->ui->when(isset($contents['path']), fn() =>
                            $this->pathCol($htmlIds, $contents, $labels)->width(6)
                        ),
                    ),
                    $this->ui->row(
                        $this->optionsCol($labels)->width(12)
                    )
                )
                ->responsive(true)->wrapped(false)->setId($htmlIds['formId'])
            )->width(12),
            $this->ui->col()->width(12)->setId('dbadmin-command-history'),
            $this->ui->col()->width(12)->setId('dbadmin-command-results')
        );
    }
}
