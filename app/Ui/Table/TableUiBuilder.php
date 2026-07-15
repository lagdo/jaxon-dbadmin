<?php

namespace Lagdo\DbAdmin\App\Ui\Table;

use Jaxon\Script\Call\JxnCall;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Ddl\Column;
use Lagdo\DbAdmin\App\Ui\PageTrait;
use Lagdo\DbAdmin\App\Ui\Tab\Tab;
use Lagdo\DbAdmin\App\Ui\Table\Column\ColumnFieldTrait;
use Lagdo\DbAdmin\Support\Driver\UiDto\Ddl\ColumnFormDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\Ddl\TableFormDto;
use Lagdo\DbAdmin\Support\Translator;
use Lagdo\UiBuilder\BuilderInterface;
use Closure;

use function array_filter;
use function array_values;
use function count;
use function Jaxon\jo;
use function Jaxon\rq;
use function sprintf;

class TableUiBuilder
{
    use PageTrait;
    use TableTrait;
    use ColumnFieldTrait;

    /**
     * @var array<JxnCall>
     */
    private array $rq = [];

    /**
     * @var Closure
     */
    private Closure $typeIsAutoIncrementable;

    /**
     * @var string
     */
    private const tableToggleClass = 'dbadmin-table-field-toggle';

    /**
     * @var string
     */
    private const columnToggleClass = 'dbadmin-table-column-toggle';

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
     * @var JxnCall
     */
    private function rqHeader(): JxnCall
    {
        return $this->rq['header'] ??= rq(Column\Header::class);
    }

    /**
     * @var JxnCall
     */
    private function rqWrapper(): JxnCall
    {
        return $this->rq['wrapper'] ??= rq(Column\Wrapper::class);
    }

    /**
     * @var JxnCall
     */
    private function rqMove(): JxnCall
    {
        return $this->rq['move'] ??= rq(Column\MoveFunc::class);
    }

    /**
     * @var JxnCall
     */
    private function rqCreate(): JxnCall
    {
        return $this->rq['create'] ??= rq(Column\CreateFunc::class);
    }

    /**
     * @var JxnCall
     */
    private function rqUpdate(): JxnCall
    {
        return $this->rq['update'] ??= rq(Column\UpdateFunc::class);
    }

    /**
     * @var JxnCall
     */
    private function rqDelete(): JxnCall
    {
        return $this->rq['delete'] ??= rq(Column\DeleteFunc::class);
    }

    /**
     * @param Closure $autoIncrementChecker
     *
     * @return self
     */
    public function setAutoIncrementChecker(Closure $autoIncrementChecker): self
    {
        $this->typeIsAutoIncrementable = $autoIncrementChecker;
        return $this;
    }

    /**
     * @return array
     */
    private function autoIncrementIsEditable(): array
    {
        $primaryKeyFilter = fn(ColumnFormDto $input) => $input->values()->primary &&
            ($this->typeIsAutoIncrementable)($input->values()->type);
        $primaryKeyInputs = array_filter($this->inputs(), $primaryKeyFilter);

        $autoIncrementFilter = fn(ColumnFormDto $input) => $input->values()->autoIncrement;
        $autoIncrementInputs = array_values(array_filter($this->inputs(), $autoIncrementFilter));
        $hasAutoIncrement = count($autoIncrementInputs) === 1;
        $autoIncrementColumn = $hasAutoIncrement ? $autoIncrementInputs[0]->values()->name : '';

        return [$autoIncrementColumn, count($primaryKeyInputs) === 1 || $hasAutoIncrement];
    }

    /**
     * @param array $formValues
     *
     * @return string
     */
    public function headerForm(array $formValues): string
    {
        $hasEngines = count($this->engines()) > 0;
        $support = $this->support(['table_collation', 'comment']);
        $hasCollations = $support['table_collation'] && count($this->collations()) > 0;
        $engineClass = $hasCollations ? 'dbadmin-table-column-middle' : 'dbadmin-table-column-left';
        $hasComment = $support['comment'];
        $commentClass = match(true) {
            !$hasComment => '',
            !$hasCollations && !$hasEngines => 'dbadmin-table-column-left',
            $hasCollations && $hasEngines => 'dbadmin-table-column-right',
            default => 'dbadmin-table-column-middle',
        };
 
        /** @var TableFormDto */
        $table = $this->metadata['table'];
        $values = $table->values();
        [$autoIncrementColumn, $aiEditable] = $this->autoIncrementIsEditable();

        return $this->ui->build(
            $this->ui->form(
                $this->ui->row(
                    $this->ui->col(
                        $this->ui->input()
                            ->setType('text')
                            ->setName('name')
                            ->setValue($formValues['name'] ?? $values->name)
                            ->setPlaceholder('Name')
                    )->setClass('dbadmin-table-column-left')
                        ->width(3),
                    $this->ui->col(
                        $this->ui->inputGroup(
                            $this->ui->checkbox()
                                ->checked($formValues['hasAutoIncrement'] ?? false)
                                ->setValue('1')
                                ->setName('hasAutoIncrement')
                                ->when(!$aiEditable, fn($input) => $this->disable($input, true)),
                            $this->ui->label($autoIncrementColumn),
                            $this->ui->input()
                                ->setName('autoIncrement')
                                ->setPlaceholder('Auto Incr.')
                                ->setValue($formValues['autoIncrement'] ?? $values->autoIncrement)
                                ->when(!$aiEditable, fn($input) => $this->disable($input, true))
                        )
                    )->setClass('dbadmin-table-column-middle')
                        ->width(3)
                )->setClass('dbadmin-table-edit-row'),
                $this->ui->when($hasCollations || $hasEngines || $hasComment, fn() =>
                    $this->ui->row(
                        $this->ui->when($hasCollations, fn() =>
                            $this->ui->col(
                                $this->getCollationSelect($formValues['collation'] ?? $values->collation)
                                    ->setName('collation')
                            )->setClass('dbadmin-table-column-left')
                                ->width(3)
                        ),
                        $this->ui->when($hasEngines, fn() =>
                            $this->ui->col(
                                $this->getEngineSelect($formValues['engine'] ?? $values->engine)
                                    ->setName('engine')
                            )->setClass($engineClass)
                                ->width(3)
                        ),
                        $this->ui->col(
                            $this->ui->when($hasComment, fn() =>
                                $this->ui->inputGroup(
                                    $this->ui->checkbox()
                                        ->checked($formValues['setComment'] ?? false)
                                        ->setValue('1')
                                        ->setName('setComment'),
                                    $this->ui->input()
                                        ->setType('text')
                                        ->setName('comment')
                                        ->setValue($formValues['comment'] ?? $values->comment ?? '')
                                        ->setPlaceholder($this->trans->lang('Comment'))
                                )
                            )
                        )->setClass($commentClass)
                            ->width(6)
                    )->setClass('dbadmin-table-edit-row ' . self::tableToggleClass)
                        ->setStyle('display: none;')
                )
            )->setId($this->tableFormId())
        );
    }

    /**
     * @return string
     */
    public function content(): string
    {
        $support = $this->support(['columns']);

        return $this->ui->build(
            $this->ui->div(
                $this->ui->div(
                    $this->trans->lang('Table')
                )->setClass('dbadmin-table-edit-header-title'),
                $this->ui->div(
                    $this->ui->button($this->ui->html('<i class="fa fa-expand"></i>'))
                        ->primary()
                        ->jxnClick(jo('jaxon.dbadmin')->toggleVisibility(
                            $this->tableFormId(), self::tableToggleClass))
                )->setClass('dbadmin-table-edit-header-buttons')
            )->setClass('dbadmin-table-edit-header'),
            $this->ui->div()
                ->setClass('dbadmin-table-edit-column')
                ->tbnBindApp($this->rqHeader()),
            $this->ui->div(
                $this->ui->div(
                    $this->trans->lang('Columns')
                )->setClass('dbadmin-table-edit-header-title'),
                $this->ui->div(
                    $this->ui->when($support['columns'], fn() =>
                        $this->ui->buttonGroup(
                            $this->ui->button($this->ui->html('<i class="fa fa-expand"></i>'))
                                ->primary()
                                ->jxnClick(jo('jaxon.dbadmin')->toggleVisibility(
                                    $this->columnsFormId(), self::columnToggleClass)),
                            $this->ui->button($this->ui->html('<i class="fa fa-plus"></i>'))
                                ->primary()
                                ->jxnClick($this->rqCreate()->add())
                        )
                    )
                )->setClass('dbadmin-table-edit-header-buttons')
            )->setClass('dbadmin-table-edit-header'),
            $this->ui->form(
                $this->ui->div()->tbnBindApp($this->rqWrapper())
            )->setId($this->columnsFormId())
        );
    }

    /**
     * @param ColumnFormDto $input
     * @param string $columnId
     *
     * @return array
     */
    private function getColumnMenuEntries(ColumnFormDto $input, string $columnId): array
    {
        $cancelQuestion = 'Confirm the cancellation?';
        $visibilityEntry = [
            'label' => '<i class="fa fa-expand"></i>',
            'handler' => jo('jaxon.dbadmin')
                ->toggleVisibility($this->columnDivId($columnId), self::columnToggleClass),
        ];
        if ($input->dropped()) {
            return [$visibilityEntry, [
                'label' => $this->ui->text('Cancel'),
                'handler' => $this->rqDelete()->cancel($columnId)->confirm($cancelQuestion),
            ]];
        }

        $support = $this->support(['move_col', 'drop_col']);
        // $movableUp = $support['move_col'] && $input->position > 0;
        // $movableDown = $support['move_col'] &&
        //     $input->position < count($this->inputs()) - 1;
        $isAdded = $input->added();
        $removable = $isAdded || $support['drop_col'];
        $removeText = $isAdded ? 'Cancel' : 'Drop';
        $removeQuestion = $isAdded ? 'Drop this new colum?' :
            "Drop the \"{$input->column->name}\" column?";

        $menuEntries = [$visibilityEntry, [
            'label' => $this->ui->text('Edit'),
            'handler' => $this->rqUpdate()->edit($columnId),
        ]/*, [
            'label' => $this->ui->text('Add'),
            'handler' => $this->rqCreate()->add($columnId),
        ]*/];
        // if ($movableUp) {
        //     $menuEntries[] = [
        //         'label' => $this->ui->text('Up'),
        //         'handler' => $this->rqMove()->up($columnId),
        //     ];
        // }
        // if ($movableDown) {
        //     $menuEntries[] = [
        //         'label' => $this->ui->text('Down'),
        //         'handler' => $this->rqMove()->down($columnId),
        //     ];
        // }
        if ($input->edited()) {
            $menuEntries[] = [
                'label' => $this->ui->text('Cancel'),
                'handler' => $this->rqUpdate()->cancel($columnId)->confirm($cancelQuestion),
            ];
        }
        if ($removable) {
            $menuEntries[] = [
                'label' => $this->ui->text($removeText),
                'handler' => $this->rqDelete()->exec($columnId)->confirm($removeQuestion),
            ];
        }

        return $menuEntries;
    }

    /**
     * @param ColumnFormDto $input
     * @param string $columnId
     *
     * @return mixed
     */
    protected function columnUiInput(ColumnFormDto $input, string $columnId): mixed
    {
        $editPrefix = sprintf("columns[%d]", $input->position);
        $support = $this->support(['comment']);
        $columnToggleClass = self::columnToggleClass;

        return $this->ui->div(
            // First line
            $this->ui->row(
                $this->ui->col(
                    $this->getColumnNameField($input, "{$editPrefix}[name]")
                        ->setPlaceholder($this->trans->lang('Name'))
                        ->with(fn($input) => $this->disable($input, true))
                )->width(3)
                    ->setClass('dbadmin-table-column-left'),
                $this->ui->col(
                    $this->getColumnTypeField($input, "{$editPrefix}[type]")
                        ->with(fn($input) => $this->disable($input))
                )->width(3)
                    ->setClass('dbadmin-table-column-middle'),
                $this->ui->col(
                    $this->ui->row(
                        $this->ui->col(
                            $this->getColumnLengthField($input, "{$editPrefix}[length]")
                                ->with(fn($input) => $this->disable($input))
                        )->width(3)
                            ->setClass('dbadmin-table-column-left'),
                        $this->ui->col(
                            $this->ui->inputGroup(
                                $this->ui->input('')
                                    ->setPlaceholder('Primary')
                                    ->with(fn($input) => $this->disable($input, true)),
                                $this->getColumnPrimaryField($input, "{$editPrefix}[primary]")
                                    ->with(fn($input) => $this->disable($input, false))
                            )
                        )->width(3)
                            ->setClass('dbadmin-table-column-middle'),
                        $this->ui->col(
                            $this->ui->div(
                                $this->ui->div(
                                    $this->ui->inputGroup(
                                        $this->ui->input('')
                                            ->setPlaceholder('Auto Inc.')
                                            ->with(fn($input) => $this->disable($input, true)),
                                        $this->getColumnAutoIncrementField($input, "{$editPrefix}[autoIncrement]")
                                            ->with(fn($input) => $this->disable($input, false))
                                    )
                                )->setStyle('flex: 1'),
                                $this->ui->div(
                                    $this->buttonMenuComponent($this->getColumnMenuEntries($input, $columnId))
                                        ->setClass('dbadmin-table-column-buttons')
                                )->setStyle('width:90px; padding-left:5px;')
                            )->setStyle('display:flex; flex-direction:row; align-items:flex-start;')
                        )->width(6)
                            ->setClass('dbadmin-table-column-right')
                    )->addClass('nested-row')
                )->setClass('dbadmin-table-column-right')
                    ->width(6)
            )->setClass("dbadmin-table-edit-row {$this->formColumnClass}"),
            // Second line
            $this->ui->row(
                $this->ui->col(
                    $this->getColumnUnsignedField($input, "{$editPrefix}[unsigned]")
                        ->with(fn($input) => $this->disable($input))
                )->setClass('dbadmin-table-column-left')
                    ->width(3),
                $this->ui->col(
                    $this->getColumnCollationField($input, "{$editPrefix}[collation]")
                        ->with(fn($input) => $this->disable($input))
                )->setClass('dbadmin-table-column-middle')
                    ->width(3),
                $this->ui->col(
                    $this->ui->row(
                        $this->ui->col(
                            $this->ui->inputGroup(
                                $this->ui->input('')
                                    ->setPlaceholder('Nullable')
                                    ->with(fn($input) => $this->disable($input, true)),
                                $this->getColumnNullableField($input, "{$editPrefix}[null]")
                                    ->with(fn($input) => $this->disable($input, false))
                            )
                        )->width(3)
                            ->setClass('dbadmin-table-column-left'),
                        $this->ui->col(
                            $this->getColumnDefaultField($input, "{$editPrefix}[generated]",
                                "{$editPrefix}[default]", $this->trans->lang('Default value'))
                        )->width(9)
                            ->setClass('dbadmin-table-column-right')
                    )->addClass('nested-row')
                )->setClass('dbadmin-table-column-right')
                    ->width(6),
            )->setClass("dbadmin-table-edit-row {$this->formColumnClass} $columnToggleClass")
                ->setStyle('display: none;'),
            // Third line
            $this->ui->row(
                $this->ui->col(
                    $this->getColumnOnUpdateField($input, "{$editPrefix}[onUpdate]")
                        ->with(fn($input) => $this->disable($input))
                )->setClass('dbadmin-table-column-left')
                    ->width(3),
                $this->ui->col(
                    $this->getColumnOnDeleteField($input, "{$editPrefix}[onDelete]")
                        ->with(fn($input) => $this->disable($input))
                )->setClass('dbadmin-table-column-middle')
                    ->width(3),
                $this->ui->col(
                    $this->ui->when($support['comment'], fn() =>
                        $this->getColumnCommentField($input, "{$editPrefix}[comment]",
                            "{$editPrefix}[setComment]", $this->trans->lang('Comment'), true)
                    )
                )->width(6)
                    ->setClass('dbadmin-table-column-right'),
            )->setClass("dbadmin-table-edit-row {$this->formColumnClass} $columnToggleClass")
                ->setStyle('display: none;')
        )->setId($this->columnDivId($columnId));
    }

    /**
     * @param ColumnFormDto $input
     *
     * @return mixed
     */
    private function getColumnBgColor(ColumnFormDto $input): string
    {
        return match(true) {
            $input->added() => "background-color: #e6ffe6;",
            $input->edited() => "background-color: #d9f1ff;",
            $input->dropped() => "background-color: #ffe6e6;",
            default => "background-color: white;",
        };
    }

    /**
     * @return string
     */
    public function showColumns(): string
    {
        $this->listMode = true;

        return $this->ui->inForm(fn() =>
            $this->ui->build(
                $this->ui->each($this->inputs(), fn($input, $columnId) =>
                    $this->ui->div(
                        $this->columnUiInput($input, $columnId)
                    )->setClass('dbadmin-table-edit-column')
                        ->setStyle($this->getColumnBgColor($input))
                )
            )
        );
    }
}
