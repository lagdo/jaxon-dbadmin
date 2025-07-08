<?php

namespace Lagdo\DbAdmin\Ui\Traits;

trait DatabaseTrait
{
    /**
     * @param string $formId
     * @param bool $materializedView
     * @param array $view
     *
     * @return string
     */
    public function viewForm(string $formId, bool $materializedView, array $view = []): string
    {
        return $this->html->build(
            $this->html->form(
                $this->html->formRow(
                    $this->html->formLabel()
                        ->setFor('name')->addText('Name')
                ),
                $this->html->formRow(
                    $this->html->formInput()
                        ->setType('text')->setName('name')
                        ->setPlaceholder('Name')->setValue($view['name'] ?? '')
                ),
                $this->html->formRow(
                    $this->html->formLabel()
                        ->setFor('select')->addText('SQL query')
                ),
                $this->html->formRow(
                    $this->html->formTextarea()
                        ->setRows('10')->setName('select')
                        ->setSpellcheck('false')->setWrap('on')
                        ->addText($view['select'] ?? '')
                ),
                $this->html->when($materializedView, fn() =>
                    $this->html->list(
                        $this->html->formRow(
                            $this->html->formLabel()
                                ->setFor('materialized')->addText('Materialized')
                        ),
                        $this->html->formRow(
                            $this->html->checkbox()
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
        return $this->html->col(
            $this->html->formRow(
                $this->html->formCol(
                    $this->html->label($labels['file_upload'])
                )
                ->width(4),
                $this->html->when(isset($contents['upload']), fn() =>
                    $this->html->formCol()
                        ->width(8)->addHtml($contents['upload'])
                ),
                $this->html->when(!isset($contents['upload']), fn() =>
                    $this->html->formCol()
                        ->width(8)->addHtml($contents['upload_disabled'])
                ),
            ),
            $this->html->formRow(
                $this->html->when(isset($contents['upload']), fn() =>
                    $this->html->formCol(
                        $this->html->inputGroup(
                            $this->html->button()
                                ->primary()
                                ->setId($htmlIds['sqlChooseBtnId'])
                                ->addHtml($labels['select'] . '&hellip;'),
                            $this->html->input()
                                ->setType('file')->setName('sql_files[]')
                                ->setId($htmlIds['sqlFilesInputId'])
                                ->setMultiple('multiple')
                                ->setStyle('display:none;'),
                            $this->html->formInput()
                                ->setType('text')->setReadonly('readonly')
                        )
                        ->setId($htmlIds['sqlFilesDivId'])
                    )
                    ->width(12)
                )
            ),
            $this->html->formRow(
                $this->html->formCol(
                    $this->html->button()
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
        return $this->html->col(
            $this->html->formRow(
                $this->html->formCol(
                    $this->html->label($labels['from_server'])
                )
                ->width(4),
                $this->html->formCol(
                    $this->html->span()->addText($labels['path'])
                )
                ->width(8)
            ),
            $this->html->formRow(
                $this->html->formCol(
                    $this->html->formInput()
                        ->setType('text')
                        ->setValue($contents['path'])
                        ->setReadonly('readonly')
                )
                ->width(12)
            ),
            $this->html->formRow(
                $this->html->formCol(
                    $this->html->button()
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
        return $this->html->col(
            $this->html->formRow(
                $this->html->formCol(
                    // Actually an offset. TODO: a parameter for that.
                    $this->html->text()
                        ->addHtml('&nbsp;')
                )->width(3),
                $this->html->formCol(
                    $this->html->inputGroup(
                        $this->html->text()
                            ->addText($labels['error_stops']),
                        $this->html->checkbox()
                            ->setName('error_stops')
                    )
                )
                ->width(3),
                $this->html->formCol(
                    $this->html->inputGroup(
                        $this->html->text()
                            ->addText($labels['only_errors']),
                        $this->html->checkbox()
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
        return $this->html->build(
            $this->html->col()->width(12)->setId('dbadmin-command-details'),
            $this->html->col(
                $this->html->form(
                    $this->html->row(
                        $this->importFileCol($htmlIds, $contents, $labels)->width(6),
                        $this->html->when(isset($contents['path']), fn() =>
                            $this->importPathCol($htmlIds, $contents, $labels)->width(6)
                        ),
                    ),
                    $this->html->row(
                        $this->importOptionsCol($labels)->width(12)
                    )
                )
                ->responsive(true)->wrapped(false)->setId($htmlIds['formId'])
            )->width(12),
            $this->html->col()->width(12)->setId('dbadmin-command-history'),
            $this->html->col()->width(12)->setId('dbadmin-command-results')
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
        return $this->html->col(
            $this->html->formRow(
                $this->html->formCol(
                    $this->html->label($options['output']['label'])
                        ->setFor('output')
                )
                ->width(3),
                $this->html->formCol(
                    $this->html->each($options['output']['options'], fn($label, $value) =>
                        $this->html->list(
                            $this->html->radio()
                                ->checked($options['output']['value'] === $value)
                                ->setName('output'),
                            $this->html->text()
                                ->addHtml('&nbsp;' . $label . '&nbsp;')
                        )
                    )
                )
                ->width(8)
            ),
            $this->html->formRow(
                $this->html->formCol(
                    $this->html->label($options['format']['label'])
                        ->setFor('format')
                )
                ->width(3),
                $this->html->formCol(
                    $this->html->each($options['format']['options'], fn($label, $value) =>
                        $this->html->list(
                            $this->html->radio()
                                ->checked($options['format']['value'] === $value)
                                ->setName('format'),
                            $this->html->text()
                                ->addHtml('&nbsp;' . $label . '&nbsp;')
                        )
                    )
                )
                ->width(8)
            ),
            $this->html->when(isset($options['db_style']), fn() =>
                $this->html->formRow(
                    $this->html->formCol(
                        $this->html->label($options['db_style']['label'])
                            ->setFor('db_style')
                    )
                    ->width(3),
                    $this->html->formCol(
                        $this->html->formSelect(
                            $this->html->each($options['db_style']['options'], fn($label) =>
                                $this->html->option($label)
                                    ->selected($options['db_style']['value'] == $label)
                            )
                        )
                        ->setName('db_style')
                    )
                    ->width(8)
                )
            ),
            $this->html->when(isset($options['routines']) || isset($options['events']), fn() =>
                $this->html->formRow(
                    $this->html->formCol(
                        // Actually an offset. TODO: a parameter for that.
                        $this->html->text()->addHtml('&nbsp;')
                    )
                    ->width(3),
                    $this->html->when(isset($options['routines']), fn() =>
                        $this->html->formCol(
                            $this->html->checkbox()
                                ->checked($options['routines']['checked'])
                                ->setName('routines')
                                ->setValue($options['routines']['value']),
                            $this->html->text()
                                ->addHtml('&nbsp;' . $options['routines']['label'])
                        )
                        ->width(4)
                    ),
                    $this->html->when(isset($options['events']), fn() =>
                        $this->html->formCol(
                            $this->html->checkbox()
                                ->checked($options['events']['checked'])
                                ->setName('events')
                                ->setValue($options['events']['value']),
                            $this->html->text()
                                ->addHtml('&nbsp;' . $options['events']['label'])
                        )
                        ->width(4)
                    )
                ),
            ),
            $this->html->formRow(
                $this->html->formCol(
                    $this->html->label($options['table_style']['label'])
                        ->setFor('table_style')
                )
                ->width(3),
                $this->html->formCol(
                    $this->html->formSelect(
                        $this->html->each($options['table_style']['options'], fn($label) =>
                            $this->html->option($label)
                                ->selected($options['table_style']['value'] == $label)
                        )
                    )
                    ->setName('table_style')
                )
                ->width(8)
            ),
            $this->html->formRow(
                $this->html->formCol(
                    // Actually an offset. TODO: a parameter for that.
                    $this->html->text()->addHtml('&nbsp;')
                )
                ->width(3),
                $this->html->formCol(
                    $this->html->checkbox()
                        ->checked($options['auto_increment']['checked'])
                        ->setName('auto_increment')
                        ->setValue($options['auto_increment']['value']),
                    $this->html->text()
                        ->addHtml('&nbsp;' . $options['auto_increment']['label'])
                )
                ->width(4),
                $this->html->when(isset($options['triggers']), fn() =>
                    $this->html->formCol(
                        $this->html->checkbox()
                            ->checked($options['triggers']['checked'])
                            ->setName('triggers')
                            ->setValue($options['triggers']['value']),
                        $this->html->text()
                            ->addHtml('&nbsp;' . $options['triggers']['label'])
                    )
                    ->width(4),
                )
            ),
            $this->html->formRow(
                $this->html->formCol(
                    $this->html->label($options['data_style']['label'])
                        ->setFor('data_style')
                )
                ->width(3),
                $this->html->formCol(
                    $this->html->formSelect(
                        $this->html->each($options['data_style']['options'], fn($label) =>
                            $this->html->option($label)
                                ->selected($options['data_style']['value'] == $label)
                        )
                    )
                    ->setName('data_style')
                )
                ->width(8)
            ),
            $this->html->formRow(
                $this->html->formCol(
                    // Actually an offset. TODO: a parameter for that.
                    $this->html->text()->addHtml('&nbsp;')
                )
                ->width(3),
                $this->html->formCol(
                    $this->html->button()
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
        return $this->html->col(
            $this->html->when(count($databases) > 0, fn() =>
                $this->html->table(
                    $this->html->thead(
                        $this->html->tr(
                            $this->html->th(
                                $this->html->checkbox()
                                    ->selected(true)
                                    ->setId($htmlIds['databaseNameId'] . '-all'),
                                $this->html->text()
                                    ->addHtml('&nbsp;' . $databases['headers'][0])
                            ),
                            $this->html->th(
                                $this->html->checkbox()
                                    ->selected(true)
                                    ->setId($htmlIds['tableDataId'] . '-all'),
                                $this->html->text()
                                    ->addHtml('&nbsp;' . $databases['headers'][1])
                            )
                        )
                    ),
                    $this->html->tbody(
                        $this->html->each($databases['details'], fn($database) =>
                            $this->html->tr(
                                $this->html->td(
                                    $this->html->checkbox()
                                        ->selected(true)
                                        ->setName('database_list[]')
                                        ->setClass($htmlIds['databaseNameId'])
                                        ->setValue($database['name']),
                                    $this->html->text()
                                        ->addHtml('&nbsp;' . $database['name'])
                                ),
                                $this->html->td(
                                    $this->html->checkbox()
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
            $this->html->when(count($tables) > 0, fn() =>
                $this->html->table(
                    $this->html->thead(
                        $this->html->tr(
                            $this->html->th(
                                $this->html->checkbox()
                                    ->selected(true)
                                    ->setId($htmlIds['tableNameId'] . '-all'),
                                $this->html->text()
                                    ->addHtml('&nbsp;' . $tables['headers'][0])
                            ),
                            $this->html->th(
                                $this->html->checkbox()
                                    ->selected(true)
                                    ->setId($htmlIds['tableDataId'] . '-all'),
                                $this->html->text()
                                    ->addHtml('&nbsp;' . $tables['headers'][1])
                            )
                        )
                    ),
                    $this->html->tbody(
                        $this->html->each($tables['details'], fn($table) =>
                            $this->html->tr(
                                $this->html->td(
                                    $this->html->checkbox()
                                        ->selected(true)
                                        ->setName('table_list[]')
                                        ->setClass($htmlIds['tableNameId'])
                                        ->setValue($table['name']),
                                    $this->html->text()
                                        ->addHtml('&nbsp;' . $table['name'])
                                ),
                                $this->html->td(
                                    $this->html->checkbox()
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
        return $this->html->build(
            $this->html->col(
                $this->html->form(
                    $this->html->row(
                        $this->exportOutputCol($htmlIds, $options, $labels)->width(7),
                        $this->exportItemsCol($htmlIds, $databases, $tables)->width(5)
                    )
                )
                ->responsive(true)->wrapped(false)->setId($htmlIds['formId'])
            )
            ->width(12),
            $this->html->col()->width(12)->setId('dbadmin-export-results')
        );
    }
}
