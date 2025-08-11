<?php

namespace Lagdo\DbAdmin\Ui\Command;

use Lagdo\DbAdmin\Translator;
use Lagdo\UiBuilder\BuilderInterface;

class ExportUiBuilder
{
    /**
     * @param Translator $trans
     * @param BuilderInterface $ui
     */
    public function __construct(protected Translator $trans, protected BuilderInterface $ui)
    {}

    /**
     * @param array $htmlIds
     * @param array $options
     * @param array $labels
     *
     * @return mixed
     */
    private function outputCol(array $htmlIds, array $options, array $labels): mixed
    {
        return $this->ui->col(
            $this->ui->formRow(
                $this->ui->formCol(
                    $this->ui->label($options['output']['label'])
                        ->setFor('output')
                )
                ->width(3),
                $this->ui->formCol(
                    $this->ui->each($options['output']['options'], fn($label, $value) =>
                        $this->ui->list(
                            $this->ui->radio()
                                ->checked($options['output']['value'] === $value)
                                ->setName('output'),
                            $this->ui->html('&nbsp;' . $label . '&nbsp;')
                        )
                    )
                )
                ->width(8)
            ),
            $this->ui->formRow(
                $this->ui->formCol(
                    $this->ui->label($options['format']['label'])
                        ->setFor('format')
                )
                ->width(3),
                $this->ui->formCol(
                    $this->ui->each($options['format']['options'], fn($label, $value) =>
                        $this->ui->list(
                            $this->ui->radio()
                                ->checked($options['format']['value'] === $value)
                                ->setName('format'),
                            $this->ui->html('&nbsp;' . $label . '&nbsp;')
                        )
                    )
                )
                ->width(8)
            ),
            $this->ui->when(isset($options['db_style']), fn() =>
                $this->ui->formRow(
                    $this->ui->formCol(
                        $this->ui->label($options['db_style']['label'])
                            ->setFor('db_style')
                    )
                    ->width(3),
                    $this->ui->formCol(
                        $this->ui->formSelect(
                            $this->ui->each($options['db_style']['options'], fn($label) =>
                                $this->ui->option($label)
                                    ->selected($options['db_style']['value'] == $label)
                            )
                        )
                        ->setName('db_style')
                    )
                    ->width(8)
                )
            ),
            $this->ui->when(isset($options['routines']) || isset($options['events']), fn() =>
                $this->ui->formRow(
                    $this->ui->formCol(
                        // Actually an offset. TODO: a parameter for that.
                        $this->ui->html('&nbsp;')
                    )
                    ->width(3),
                    $this->ui->when(isset($options['routines']), fn() =>
                        $this->ui->formCol(
                            $this->ui->checkbox()
                                ->checked($options['routines']['checked'])
                                ->setName('routines')
                                ->setValue($options['routines']['value']),
                            $this->ui->html('&nbsp;' . $options['routines']['label'])
                        )
                        ->width(4)
                    ),
                    $this->ui->when(isset($options['events']), fn() =>
                        $this->ui->formCol(
                            $this->ui->checkbox()
                                ->checked($options['events']['checked'])
                                ->setName('events')
                                ->setValue($options['events']['value']),
                            $this->ui->html('&nbsp;' . $options['events']['label'])
                        )
                        ->width(4)
                    )
                ),
            ),
            $this->ui->formRow(
                $this->ui->formCol(
                    $this->ui->label($options['table_style']['label'])
                        ->setFor('table_style')
                )
                ->width(3),
                $this->ui->formCol(
                    $this->ui->formSelect(
                        $this->ui->each($options['table_style']['options'], fn($label) =>
                            $this->ui->option($label)
                                ->selected($options['table_style']['value'] == $label)
                        )
                    )
                    ->setName('table_style')
                )
                ->width(8)
            ),
            $this->ui->formRow(
                $this->ui->formCol(
                    // Actually an offset. TODO: a parameter for that.
                    $this->ui->html('&nbsp;')
                )
                ->width(3),
                $this->ui->formCol(
                    $this->ui->checkbox()
                        ->checked($options['auto_increment']['checked'])
                        ->setName('auto_increment')
                        ->setValue($options['auto_increment']['value']),
                    $this->ui->html('&nbsp;' . $options['auto_increment']['label'])
                )
                ->width(4),
                $this->ui->when(isset($options['triggers']), fn() =>
                    $this->ui->formCol(
                        $this->ui->checkbox()
                            ->checked($options['triggers']['checked'])
                            ->setName('triggers')
                            ->setValue($options['triggers']['value']),
                        $this->ui->html('&nbsp;' . $options['triggers']['label'])
                    )
                    ->width(4),
                )
            ),
            $this->ui->formRow(
                $this->ui->formCol(
                    $this->ui->label($options['data_style']['label'])
                        ->setFor('data_style')
                )
                ->width(3),
                $this->ui->formCol(
                    $this->ui->formSelect(
                        $this->ui->each($options['data_style']['options'], fn($label) =>
                            $this->ui->option($label)
                                ->selected($options['data_style']['value'] == $label)
                        )
                    )
                    ->setName('data_style')
                )
                ->width(8)
            ),
            $this->ui->formRow(
                $this->ui->formCol(
                    // Actually an offset. TODO: a parameter for that.
                    $this->ui->html('&nbsp;')
                )
                ->width(3),
                $this->ui->formCol(
                    $this->ui->button($this->ui->text($labels['export']))
                        ->fullWidth()->primary()
                        ->setId($htmlIds['btnId'])
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
    private function itemsCol(array $htmlIds, array $databases, array $tables): mixed
    {
        return $this->ui->col(
            $this->ui->when(count($databases) > 0, fn() =>
                $this->ui->table(
                    $this->ui->thead(
                        $this->ui->tr(
                            $this->ui->th(
                                $this->ui->checkbox()
                                    ->selected(true)
                                    ->setId($htmlIds['databaseNameId'] . '-all'),
                                $this->ui->html('&nbsp;' . $databases['headers'][0])
                            ),
                            $this->ui->th(
                                $this->ui->checkbox()
                                    ->selected(true)
                                    ->setId($htmlIds['tableDataId'] . '-all'),
                                $this->ui->html('&nbsp;' . $databases['headers'][1])
                            )
                        )
                    ),
                    $this->ui->tbody(
                        $this->ui->each($databases['details'], fn($database) =>
                            $this->ui->tr(
                                $this->ui->td(
                                    $this->ui->checkbox()
                                        ->selected(true)
                                        ->setName('database_list[]')
                                        ->setClass($htmlIds['databaseNameId'])
                                        ->setValue($database['name']),
                                    $this->ui->html('&nbsp;' . $database['name'])
                                ),
                                $this->ui->td(
                                    $this->ui->checkbox()
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
            $this->ui->when(count($tables) > 0, fn() =>
                $this->ui->table(
                    $this->ui->thead(
                        $this->ui->tr(
                            $this->ui->th(
                                $this->ui->checkbox()
                                    ->selected(true)
                                    ->setId($htmlIds['tableNameId'] . '-all'),
                                $this->ui->html('&nbsp;' . $tables['headers'][0])
                            ),
                            $this->ui->th(
                                $this->ui->checkbox()
                                    ->selected(true)
                                    ->setId($htmlIds['tableDataId'] . '-all'),
                                $this->ui->html('&nbsp;' . $tables['headers'][1])
                            )
                        )
                    ),
                    $this->ui->tbody(
                        $this->ui->each($tables['details'], fn($table) =>
                            $this->ui->tr(
                                $this->ui->td(
                                    $this->ui->checkbox()
                                        ->selected(true)
                                        ->setName('table_list[]')
                                        ->setClass($htmlIds['tableNameId'])
                                        ->setValue($table['name']),
                                    $this->ui->html('&nbsp;' . $table['name'])
                                ),
                                $this->ui->td(
                                    $this->ui->checkbox()
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
    public function page(array $htmlIds, array $databases, array $tables,
        array $options, array $labels): string
    {
        return $this->ui->build(
            $this->ui->col(
                $this->ui->form(
                    $this->ui->row(
                        $this->outputCol($htmlIds, $options, $labels)->width(7),
                        $this->itemsCol($htmlIds, $databases, $tables)->width(5)
                    )
                )
                ->responsive(true)->wrapped(false)->setId($htmlIds['formId'])
            )
            ->width(12),
            $this->ui->col()->width(12)->setId('dbadmin-export-results')
        );
    }
}
