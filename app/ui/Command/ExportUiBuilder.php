<?php

namespace Lagdo\DbAdmin\Ui\Command;

use Jaxon\Script\Call\JxnCall;
use Lagdo\DbAdmin\Db\Translator;
use Lagdo\UiBuilder\BuilderInterface;

use function Jaxon\je;

class ExportUiBuilder
{
    private string $formId = 'dbadmin-main-export-form';

    public string $databaseNameId = 'dbadmin-export-database-name';

    public string $databaseDataId = 'dbadmin-export-database-data';

    public string $tableNameId = 'dbadmin-export-table-name';

    public string $tableDataId = 'dbadmin-export-table-data';

    /**
     * @param Translator $trans
     * @param BuilderInterface $ui
     */
    public function __construct(protected Translator $trans, protected BuilderInterface $ui)
    {}

    /**
     * @param JxnCall $rqExport
     * @param array $options
     *
     * @return mixed
     */
    private function optionsCol(JxnCall $rqExport, array $options): mixed
    {
        $hasDbOptions = isset($options['types']) ||
            isset($options['routines']) || isset($options['events']);
        return $this->ui->col(
            $this->ui->formRow(
                $this->ui->formCol(
                    $this->ui->label($options['output']['label'])
                        ->setFor('output')
                )->width(3),
                $this->ui->formCol(
                    $this->ui->each($options['output']['options'], fn($label, $value) =>
                        $this->ui->list(
                            $this->ui->radio()
                                ->checked($options['output']['value'] === $value)
                                ->setValue($value)
                                ->setName('output'),
                            $this->ui->html('&nbsp;' . $label . '&nbsp;')
                        )
                    )
                )->width(8)
            ),
            $this->ui->formRow(
                $this->ui->formCol(
                    $this->ui->label($options['format']['label'])
                        ->setFor('format')
                )->width(3),
                $this->ui->formCol(
                    $this->ui->each($options['format']['options'], fn($label, $value) =>
                        $this->ui->list(
                            $this->ui->radio()
                                ->checked($options['format']['value'] === $value)
                                ->setValue($value)
                                ->setName('format'),
                            $this->ui->html('&nbsp;' . $label . '&nbsp;')
                        )
                    )
                )->width(8)
            ),
            $this->ui->when(isset($options['db_style']), fn() =>
                $this->ui->formRow(
                    $this->ui->formCol(
                        $this->ui->label($options['db_style']['label'])
                            ->setFor('db_style')
                    )->width(3),
                    $this->ui->formCol(
                        $this->ui->formSelect(
                            $this->ui->each($options['db_style']['options'], fn($label) =>
                                $this->ui->option($label)
                                    ->selected($options['db_style']['value'] == $label)
                            )
                        )->setName('db_style')
                    )->width(8)
                )
            ),
            $this->ui->when($hasDbOptions, fn() =>
                $this->ui->formRow(
                    $this->ui->formCol(
                        // Actually an offset. TODO: a parameter for that.
                        $this->ui->html('&nbsp;')
                    )->width(3),
                    $this->ui->when(isset($options['types']), fn() =>
                        $this->ui->formCol(
                            $this->ui->checkbox()
                                ->checked($options['types']['checked'])
                                ->setName('types')
                                ->setValue($options['types']['value']),
                            $this->ui->html('&nbsp;' . $options['types']['label'])
                        )->width(3)
                    ),
                    $this->ui->when(isset($options['routines']), fn() =>
                        $this->ui->formCol(
                            $this->ui->checkbox()
                                ->checked($options['routines']['checked'])
                                ->setName('routines')
                                ->setValue($options['routines']['value']),
                            $this->ui->html('&nbsp;' . $options['routines']['label'])
                        )->width(3)
                    ),
                    $this->ui->when(isset($options['events']), fn() =>
                        $this->ui->formCol(
                            $this->ui->checkbox()
                                ->checked($options['events']['checked'])
                                ->setName('events')
                                ->setValue($options['events']['value']),
                            $this->ui->html('&nbsp;' . $options['events']['label'])
                        )->width(3)
                    )
                ),
            ),
            $this->ui->formRow(
                $this->ui->formCol(
                    $this->ui->label($options['table_style']['label'])
                        ->setFor('table_style')
                )->width(3),
                $this->ui->formCol(
                    $this->ui->formSelect(
                        $this->ui->each($options['table_style']['options'], fn($label) =>
                            $this->ui->option($label)
                                ->selected($options['table_style']['value'] == $label)
                        )
                    )->setName('table_style')
                )->width(8)
            ),
            $this->ui->formRow(
                $this->ui->formCol(
                    // Actually an offset. TODO: a parameter for that.
                    $this->ui->html('&nbsp;')
                )->width(3),
                $this->ui->formCol(
                    $this->ui->checkbox()
                        ->checked($options['auto_increment']['checked'])
                        ->setName('auto_increment')
                        ->setValue($options['auto_increment']['value']),
                    $this->ui->html('&nbsp;' . $options['auto_increment']['label'])
                )->width(3),
                $this->ui->when(isset($options['triggers']), fn() =>
                    $this->ui->formCol(
                        $this->ui->checkbox()
                            ->checked($options['triggers']['checked'])
                            ->setName('triggers')
                            ->setValue($options['triggers']['value']),
                        $this->ui->html('&nbsp;' . $options['triggers']['label'])
                    )->width(3),
                )
            ),
            $this->ui->formRow(
                $this->ui->formCol(
                    $this->ui->label($options['data_style']['label'])
                        ->setFor('data_style')
                )->width(3),
                $this->ui->formCol(
                    $this->ui->formSelect(
                        $this->ui->each($options['data_style']['options'], fn($label) =>
                            $this->ui->option($label)
                                ->selected($options['data_style']['value'] == $label)
                        )
                    )->setName('data_style')
                )->width(8)
            ),
            $this->ui->formRow(
                $this->ui->formCol(
                    // Actually an offset. TODO: a parameter for that.
                    $this->ui->html('&nbsp;')
                )->width(3),
                $this->ui->formCol(
                    $this->ui->button($this->ui->text($this->trans->lang('Export')))
                        ->fullWidth()
                        ->primary()
                        ->jxnClick($rqExport->export(je($this->formId)->rd()->form()))
                )->width(4)
            )
        );
    }

    /**
     * @param array $databases
     *
     * @return mixed
     */
    private function databases(array $databases): mixed
    {
        return $this->ui->div(
            $this->ui->table(
                $this->ui->thead(
                    $this->ui->tr(
                        $this->ui->th(
                            $this->ui->checkbox()
                                ->checked(true)
                                ->setId($this->databaseNameId . '-all'),
                            $this->ui->html('&nbsp;' . $databases['headers'][0])
                        ),
                        $this->ui->th(
                            $this->ui->checkbox()
                                ->checked(true)
                                ->setId($this->databaseDataId . '-all'),
                            $this->ui->html('&nbsp;' . $databases['headers'][1])
                        )
                    )
                ),
                $this->ui->tbody(
                    $this->ui->each($databases['details'], fn($database, $pos) =>
                        $this->ui->tr(
                            $this->ui->td(
                                $this->ui->checkbox()
                                    ->checked(true)
                                    ->setName('database_list[]')
                                    ->setClass($this->databaseNameId)
                                    ->setValue($database['name'])
                                    ->setDataPos($pos),
                                $this->ui->html('&nbsp;' . $database['name'])
                            ),
                            $this->ui->td(
                                $this->ui->checkbox()
                                    ->checked(true)
                                    ->setName('database_data[]')
                                    ->setId("{$this->databaseDataId}-$pos")
                                    ->setClass($this->databaseDataId)
                                    ->setValue($database['name'])
                            )
                        )
                    )
                )
            )->responsive(true)->style('bordered')
        )->setStyle('max-height: 450px; overflow: scroll;');
    }

    /**
     * @param array $tables
     *
     * @return mixed
     */
    private function tables(array $tables): mixed
    {
        return $this->ui->div(
            $this->ui->table(
                $this->ui->thead(
                    $this->ui->tr(
                        $this->ui->th(
                            $this->ui->checkbox()
                                ->checked(true)
                                ->setId($this->tableNameId . '-all'),
                            $this->ui->html('&nbsp;' . $tables['headers'][0])
                        ),
                        $this->ui->th(
                            $this->ui->checkbox()
                                ->checked(true)
                                ->setId($this->tableDataId . '-all'),
                            $this->ui->html('&nbsp;' . $tables['headers'][1])
                        )
                    )
                ),
                $this->ui->tbody(
                    $this->ui->each($tables['details'], fn($table, $pos) =>
                        $this->ui->tr(
                            $this->ui->td(
                                $this->ui->checkbox()
                                    ->checked(true)
                                    ->setName('table_list[]')
                                    ->setClass($this->tableNameId)
                                    ->setValue($table['name'])
                                    ->setDataPos($pos),
                                $this->ui->html('&nbsp;' . $table['name'])
                            ),
                            $this->ui->td(
                                $this->ui->checkbox()
                                    ->checked(true)
                                    ->setName('table_data[]')
                                    ->setId("{$this->tableDataId}-$pos")
                                    ->setClass($this->tableDataId)
                                    ->setValue($table['name'])
                            )
                        )
                    )
                )
            )->responsive(true)->style('bordered')
        )->setStyle('max-height: 450px; overflow: scroll;');
    }

    /**
     * @param JxnCall $rqExport
     * @param array $options
     *
     * @return string
     */
    public function export(JxnCall $rqExport, array $options): string
    {
        return $this->ui->build(
            $this->ui->col(
                $this->ui->form(
                    $this->ui->row(
                        $this->optionsCol($rqExport, $options['options'])
                            ->width(6),
                        $this->ui->when(isset($options['databases']), fn() =>
                            $this->ui->col($this->databases($options['databases']))
                                ->width(6)
                        ),
                        $this->ui->when(isset($options['tables']), fn() =>
                            $this->ui->col($this->tables($options['tables']))
                                ->width(6)
                        )
                    )
                )->wrapped(false)->setId($this->formId)
            )->width(12),
            $this->ui->col()->width(12)->setId('dbadmin-export-results')
        );
    }
}
