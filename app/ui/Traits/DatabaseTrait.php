<?php

namespace Lagdo\DbAdmin\Ui\Traits;

use Lagdo\UiBuilder\BuilderInterface;

trait DatabaseTrait
{
    /**
     * @return BuilderInterface
     */
    abstract protected function builder(): BuilderInterface;

    /**
     * @param string $formId
     * @param bool $materializedView
     * @param array $view
     *
     * @return string
     */
    public function viewForm(string $formId, bool $materializedView, array $view = []): string
    {
        $html = $this->builder();
        return $html->build(
            $html->form(
                $html->formRow(
                    $html->formLabel()
                        ->setFor('name')->addText('Name')
                ),
                $html->formRow(
                    $html->formInput()
                        ->setType('text')->setName('name')
                        ->setPlaceholder('Name')->setValue($view['name'] ?? '')
                ),
                $html->formRow(
                    $html->formLabel()
                        ->setFor('select')->addText('SQL query')
                ),
                $html->formRow(
                    $html->formTextarea()
                        ->setRows('10')->setName('select')
                        ->setSpellcheck('false')->setWrap('on')
                        ->addText($view['select'] ?? '')
                ),
                $html->when($materializedView, fn() =>
                    $html->list(
                        $html->formRow(
                            $html->formLabel()
                                ->setFor('materialized')->addText('Materialized')
                        ),
                        $html->formRow(
                            $html->checkbox()
                                ->checked($view['materialized'] ?? false)
                                ->setName('materialized')
                        )
                    )
                )
            )
            ->wrapped()->setId($formId)
        );
    }

    /**
     * @param array $htmlIds
     * @param array $contents
     * @param array $labels
     *
     * @return mixed
     */
    public function importFileCol(array $htmlIds, array $contents, array $labels): mixed
    {
        $html = $this->builder();
        return $html->col(
            $html->formRow(
                $html->formCol(
                    $html->label($labels['file_upload'])
                )
                ->width(4),
                $html->when(isset($contents['upload']), fn() =>
                    $html->formCol()
                        ->width(8)->addHtml($contents['upload'])
                ),
                $html->when(!isset($contents['upload']), fn() =>
                    $html->formCol()
                        ->width(8)->addHtml($contents['upload_disabled'])
                ),
            ),
            $html->formRow(
                $html->when(isset($contents['upload']), fn() =>
                    $html->formCol(
                        $html->inputGroup(
                            $html->button()
                                ->primary()
                                ->setId($htmlIds['sqlChooseBtnId'])
                                ->addHtml($labels['select'] . '&hellip;'),
                            $html->input()
                                ->setType('file')->setName('sql_files[]')
                                ->setId($htmlIds['sqlFilesInputId'])
                                ->setMultiple('multiple')
                                ->setStyle('display:none;'),
                            $html->formInput()
                                ->setType('text')->setReadonly('readonly')
                        )
                        ->setId($htmlIds['sqlFilesDivId'])
                    )
                    ->width(12)
                )
            ),
            $html->formRow(
                $html->formCol(
                    $html->button()
                        ->fullWidth()->primary()
                        ->setId($htmlIds['sqlFilesBtnId'])
                        ->addText($labels['execute'])
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
    public function importPathCol(array $htmlIds, array $contents, array $labels): mixed
    {
        $html = $this->builder();
        return $html->col(
            $html->formRow(
                $html->formCol(
                    $html->label($labels['from_server'])
                )
                ->width(4),
                $html->formCol(
                    $html->span()->addText($labels['path'])
                )
                ->width(8)
            ),
            $html->formRow(
                $html->formCol(
                    $html->formInput()
                        ->setType('text')
                        ->setValue($contents['path'])
                        ->setReadonly('readonly')
                )
                ->width(12)
            ),
            $html->formRow(
                $html->formCol(
                    $html->button()
                        ->fullWidth()->primary()
                        ->setId($htmlIds['webFileBtnId'])
                        ->addText($labels['run_file'])
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
    public function importOptionsCol(array $labels): mixed
    {
        $html = $this->builder();
        return $html->col(
            $html->formRow(
                $html->formCol(
                    // Actually an offset. TODO: a parameter for that.
                    $html->text()
                        ->addHtml('&nbsp;')
                )->width(3),
                $html->formCol(
                    $html->inputGroup(
                        $html->text()
                            ->addText($labels['error_stops']),
                        $html->checkbox()
                            ->setName('error_stops')
                    )
                )
                ->width(3),
                $html->formCol(
                    $html->inputGroup(
                        $html->text()
                            ->addText($labels['only_errors']),
                        $html->checkbox()
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
    public function importPage(array $htmlIds, array $contents, array $labels): string
    {
        $html = $this->builder();
        return $html->build(
            $html->col()->width(12)->setId('dbadmin-command-details'),
            $html->col(
                $html->form(
                    $html->row(
                        $this->importFileCol($htmlIds, $contents, $labels)->width(6),
                        $html->when(isset($contents['path']), fn() =>
                            $this->importPathCol($htmlIds, $contents, $labels)->width(6)
                        ),
                    ),
                    $html->row(
                        $this->importOptionsCol($labels)->width(12)
                    )
                )
                ->responsive(true)->wrapped(false)->setId($htmlIds['formId'])
            )->width(12),
            $html->col()->width(12)->setId('dbadmin-command-history'),
            $html->col()->width(12)->setId('dbadmin-command-results')
        );
    }

    /**
     * @param array $htmlIds
     * @param array $options
     * @param array $labels
     *
     * @return mixed
     */
    private function exportOutputCol(array $htmlIds, array $options, array $labels): mixed
    {
        $html = $this->builder();
        return $html->col(
            $html->formRow(
                $html->formCol(
                    $html->label($options['output']['label'])
                        ->setFor('output')
                )
                ->width(3),
                $html->formCol(
                    $html->each($options['output']['options'], fn($label, $value) =>
                        $html->list(
                            $html->radio()
                                ->checked($options['output']['value'] === $value)
                                ->setName('output'),
                            $html->text()
                                ->addHtml('&nbsp;' . $label . '&nbsp;')
                        )
                    )
                )
                ->width(8)
            ),
            $html->formRow(
                $html->formCol(
                    $html->label($options['format']['label'])
                        ->setFor('format')
                )
                ->width(3),
                $html->formCol(
                    $html->each($options['format']['options'], fn($label, $value) =>
                        $html->list(
                            $html->radio()
                                ->checked($options['format']['value'] === $value)
                                ->setName('format'),
                            $html->text()
                                ->addHtml('&nbsp;' . $label . '&nbsp;')
                        )
                    )
                )
                ->width(8)
            ),
            $html->when(isset($options['db_style']), fn() =>
                $html->formRow(
                    $html->formCol(
                        $html->label($options['db_style']['label'])
                            ->setFor('db_style')
                    )
                    ->width(3),
                    $html->formCol(
                        $html->formSelect(
                            $html->each($options['db_style']['options'], fn($label) =>
                                $html->option($label)
                                    ->selected($options['db_style']['value'] == $label)
                            )
                        )
                        ->setName('db_style')
                    )
                    ->width(8)
                )
            ),
            $html->when(isset($options['routines']) || isset($options['events']), fn() =>
                $html->formRow(
                    $html->formCol(
                        // Actually an offset. TODO: a parameter for that.
                        $html->text()->addHtml('&nbsp;')
                    )
                    ->width(3),
                    $html->when(isset($options['routines']), fn() =>
                        $html->formCol(
                            $html->checkbox()
                                ->checked($options['routines']['checked'])
                                ->setName('routines')
                                ->setValue($options['routines']['value']),
                            $html->text()
                                ->addHtml('&nbsp;' . $options['routines']['label'])
                        )
                        ->width(4)
                    ),
                    $html->when(isset($options['events']), fn() =>
                        $html->formCol(
                            $html->checkbox()
                                ->checked($options['events']['checked'])
                                ->setName('events')
                                ->setValue($options['events']['value']),
                            $html->text()
                                ->addHtml('&nbsp;' . $options['events']['label'])
                        )
                        ->width(4)
                    )
                ),
            ),
            $html->formRow(
                $html->formCol(
                    $html->label($options['table_style']['label'])
                        ->setFor('table_style')
                )
                ->width(3),
                $html->formCol(
                    $html->formSelect(
                        $html->each($options['table_style']['options'], fn($label) =>
                            $html->option($label)
                                ->selected($options['table_style']['value'] == $label)
                        )
                    )
                    ->setName('table_style')
                )
                ->width(8)
            ),
            $html->formRow(
                $html->formCol(
                    // Actually an offset. TODO: a parameter for that.
                    $html->text()->addHtml('&nbsp;')
                )
                ->width(3),
                $html->formCol(
                    $html->checkbox()
                        ->checked($options['auto_increment']['checked'])
                        ->setName('auto_increment')
                        ->setValue($options['auto_increment']['value']),
                    $html->text()
                        ->addHtml('&nbsp;' . $options['auto_increment']['label'])
                )
                ->width(4),
                $html->when(isset($options['triggers']), fn() =>
                    $html->formCol(
                        $html->checkbox()
                            ->checked($options['triggers']['checked'])
                            ->setName('triggers')
                            ->setValue($options['triggers']['value']),
                        $html->text()
                            ->addHtml('&nbsp;' . $options['triggers']['label'])
                    )
                    ->width(4),
                )
            ),
            $html->formRow(
                $html->formCol(
                    $html->label($options['data_style']['label'])
                        ->setFor('data_style')
                )
                ->width(3),
                $html->formCol(
                    $html->formSelect(
                        $html->each($options['data_style']['options'], fn($label) =>
                            $html->option($label)
                                ->selected($options['data_style']['value'] == $label)
                        )
                    )
                    ->setName('data_style')
                )
                ->width(8)
            ),
            $html->formRow(
                $html->formCol(
                    // Actually an offset. TODO: a parameter for that.
                    $html->text()->addHtml('&nbsp;')
                )
                ->width(3),
                $html->formCol(
                    $html->button()
                        ->fullWidth()->primary()
                        ->setId($htmlIds['btnId'])
                        ->addText($labels['export'])
                )
                ->width(4)
            )
        );
    }

    /**
     * @param array $htmlIds
     * @param array $databases
     * @param array $tables
     *
     * @return mixed
     */
    public function exportItemsCol(array $htmlIds, array $databases, array $tables): mixed
    {
        $html = $this->builder();
        return $html->col(
            $html->when(count($databases) > 0, fn() =>
                $html->table(
                    $html->thead(
                        $html->tr(
                            $html->th(
                                $html->checkbox()
                                    ->selected(true)
                                    ->setId($htmlIds['databaseNameId'] . '-all'),
                                $html->text()
                                    ->addHtml('&nbsp;' . $databases['headers'][0])
                            ),
                            $html->th(
                                $html->checkbox()
                                    ->selected(true)
                                    ->setId($htmlIds['tableDataId'] . '-all'),
                                $html->text()
                                    ->addHtml('&nbsp;' . $databases['headers'][1])
                            )
                        )
                    ),
                    $html->tbody(
                        $html->each($databases['details'], fn($database) =>
                            $html->tr(
                                $html->td(
                                    $html->checkbox()
                                        ->selected(true)
                                        ->setName('database_list[]')
                                        ->setClass($htmlIds['databaseNameId'])
                                        ->setValue($database['name']),
                                    $html->text()
                                        ->addHtml('&nbsp;' . $database['name'])
                                ),
                                $html->td(
                                    $html->checkbox()
                                        ->selected(true)
                                        ->setName('database_data[]')
                                        ->setClass($htmlIds['databaseDataId'])
                                        ->setValue($database['name'])
                                )
                            )
                        )
                    )
                )
                ->responsive(true)->style('bordered')
            ),
            $html->when(count($tables) > 0, fn() =>
                $html->table(
                    $html->thead(
                        $html->tr(
                            $html->th(
                                $html->checkbox()
                                    ->selected(true)
                                    ->setId($htmlIds['tableNameId'] . '-all'),
                                $html->text()
                                    ->addHtml('&nbsp;' . $tables['headers'][0])
                            ),
                            $html->th(
                                $html->checkbox()
                                    ->selected(true)
                                    ->setId($htmlIds['tableDataId'] . '-all'),
                                $html->text()
                                    ->addHtml('&nbsp;' . $tables['headers'][1])
                            )
                        )
                    ),
                    $html->tbody(
                        $html->each($tables['details'], fn($table) =>
                            $html->tr(
                                $html->td(
                                    $html->checkbox()
                                        ->selected(true)
                                        ->setName('table_list[]')
                                        ->setClass($htmlIds['tableNameId'])
                                        ->setValue($table['name']),
                                    $html->text()
                                        ->addHtml('&nbsp;' . $table['name'])
                                ),
                                $html->td(
                                    $html->checkbox()
                                        ->selected(true)
                                        ->setName('table_data[]')
                                        ->setClass($htmlIds['tableDataId'])
                                        ->setValue($table['name'])
                                )
                            )
                        )
                    )
                )
                ->responsive(true)->style('bordered')
            ),
        );
    }

    /**
     * @param array $htmlIds
     * @param array $databases
     * @param array $tables
     * @param array $options
     * @param array $labels
     *
     * @return string
     */
    public function exportPage(array $htmlIds, array $databases, array $tables,
        array $options, array $labels): string
    {
        $html = $this->builder();
        return $html->build(
            $html->col(
                $html->form(
                    $html->row(
                        $this->exportOutputCol($htmlIds, $options, $labels)->width(7),
                        $this->exportItemsCol($htmlIds, $databases, $tables)->width(5)
                    )
                )
                ->responsive(true)->wrapped(false)->setId($htmlIds['formId'])
            )
            ->width(12),
            $html->col()->width(12)->setId('dbadmin-export-results')
        );
    }
}
