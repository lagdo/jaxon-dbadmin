<?php

namespace Lagdo\DbAdmin\App\Ui\Table;

use Jaxon\Script\Call\JxnCall;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Ddl\Column;
use Lagdo\DbAdmin\App\Ui\PageTrait;
use Lagdo\DbAdmin\App\Ui\Tab\Tab;
use Lagdo\DbAdmin\App\Ui\Table\Column\ColumnFieldTrait;
use Lagdo\DbAdmin\Support\Driver\UiDto\Ddl\ColumnFormDto;
use Lagdo\DbAdmin\Support\Translator;
use Lagdo\UiBuilder\BuilderInterface;
use Lagdo\UiBuilder\Html\Component\Component;
use Closure;

use function array_filter;
use function array_values;
use function count;
use function in_array;
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
     * @var string
     */
    private const tableToggleClass = 'dbadmin-table-field-toggle';

    /**
     * @var string
     */
    private const columnToggleClass = 'dbadmin-table-column-toggle';

    /**
     * @var Closure
     */
    private Closure $typeIsAutoIncrementable;

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
        $autoIncrementFilter = fn(ColumnFormDto $input) => $input->values()->autoIncrement;
        $autoIncrementInputs = array_values(array_filter($this->inputs(), $autoIncrementFilter));
        $hasAutoIncrement = count($autoIncrementInputs) === 1;
        $autoIncrementColumn = $hasAutoIncrement ? $autoIncrementInputs[0]->values()->name : '';

        return [$autoIncrementColumn, $autoIncrementColumn !== '' && $hasAutoIncrement];
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

        $table = $this->metadata;
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
                                ->checked($aiEditable && ($formValues['hasAutoIncrement'] ?? false))
                                ->setValue('1')
                                ->setName('hasAutoIncrement')
                                ->when(!$aiEditable, fn($elt) => $this->disable($elt, true)),
                            $this->ui->label('Auto inc. value'),
                            $this->ui->when($aiEditable, fn() =>
                                $this->ui->list(
                                    $this->ui->label($autoIncrementColumn),
                                    $this->ui->input()
                                        ->setName('autoIncrement')
                                        ->setValue($formValues['autoIncrement'] ?? $values->autoIncrement)
                                )
                            )
                        )
                    )->setClass('dbadmin-table-column-middle')
                        ->width(4)
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
     * @param string $editPrefix
     *
     * @return Component
     */
    private function typeOptionComponent(ColumnFormDto $input, string $editPrefix): Component
    {
        $makeEmptyField = fn() => $this->ui->input()
            ->setPlaceholder($this->trans->lang('Options'))
            ->with(fn($elt) => $this->disable($elt));
        [$unsignedTypes, $collationTypes, $onUpdateTypes] = $this->getColumnTypes();
        return $this->ui->pick(
            // Only for MySQL or MariaDB
            $this->ui->when(!$this->engine()->sql(), $makeEmptyField),
            $this->ui->when(in_array($input->type(), $unsignedTypes), fn() =>
                $this->getColumnUnsignedField($input, "{$editPrefix}[unsigned]")
                    ->with(fn($elt) => $this->disable($elt))
            ),
            $this->ui->when(in_array($input->type(), $collationTypes), fn() =>
                $this->getColumnCollationField($input, "{$editPrefix}[collation]")
                    ->with(fn($elt) => $this->disable($elt))
            ),
            $this->ui->when(in_array($input->type(), $onUpdateTypes), fn() =>
                $this->getColumnOnUpdateField($input, "{$editPrefix}[onUpdate]")
                    ->with(fn($elt) => $this->disable($elt))
            ),
            $this->ui->when(true, $makeEmptyField)
        );
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
     * @param ColumnFormDto $input
     * @param string $columnId
     *
     * @return Component
     */
    protected function columnUiInput(ColumnFormDto $input, string $columnId): Component
    {
        $editPrefix = sprintf("columns[%d]", $input->position);
        $columnToggleClass = self::columnToggleClass;

        return $this->ui->div(
            // First line
            $this->ui->row(
                $this->ui->col(
                    $this->getColumnNameField($input, "{$editPrefix}[name]")
                        ->setPlaceholder($this->trans->lang('Name'))
                        ->with(fn($elt) => $this->disable($elt, true))
                )->width(3)
                    ->setClass('dbadmin-table-column-left'),
                $this->ui->col(
                    $this->getColumnTypeField($input, "{$editPrefix}[type]")
                        ->with(fn($elt) => $this->disable($elt))
                )->width(2)
                    ->setClass('dbadmin-table-column-middle'),
                $this->ui->col(
                    $this->ui->row(
                        $this->ui->col(
                            $this->getColumnLengthField($input, "{$editPrefix}[length]")
                                ->with(fn($elt) => $this->disable($elt))
                        )->width(2)
                            ->setClass('dbadmin-table-column-left'),
                        $this->ui->col(
                            $this->ui->inputGroup(
                                $this->ui->input('')
                                    ->setPlaceholder('Primary')
                                    ->with(fn($elt) => $this->disable($elt, true)),
                                $this->getColumnPrimaryField($input, "{$editPrefix}[primary]")
                                    ->with(fn($elt) => $this->disable($elt, false))
                            )
                        )->width(3)
                            ->setClass('dbadmin-table-column-middle'),
                        $this->ui->col(
                            $this->ui->inputGroup(
                                $this->ui->input('')
                                    ->setPlaceholder('Auto Inc.')
                                    ->with(fn($elt) => $this->disable($elt, true)),
                                $this->getColumnAutoIncrementField($input, "{$editPrefix}[autoIncrement]")
                                    ->with(fn($elt) => $this->disable($elt, false))
                            )
                        )->width(3)
                            ->setClass('dbadmin-table-column-middle'),
                        $this->ui->col(
                            $this->ui->div(
                                $this->ui->div(
                                    $this->ui->inputGroup(
                                        $this->ui->input('')
                                            ->setPlaceholder('Nullable')
                                            ->with(fn($elt) => $this->disable($elt, true)),
                                        $this->getColumnNullableField($input, "{$editPrefix}[null]")
                                            ->with(fn($elt) => $this->disable($elt, false))
                                    )
                                )->setStyle('flex: 1'),
                                $this->ui->div(
                                    $this->buttonMenuComponent($this->getColumnMenuEntries($input, $columnId))
                                        ->setClass('dbadmin-table-column-buttons')
                                )->setStyle('width:75px; padding-left:5px;')
                            )->setStyle('display:flex; flex-direction:row; align-items:flex-start;')
                        )->width(4)
                            ->setClass('dbadmin-table-column-right')
                    )->addClass('nested-row')
                )->width(7)
                    ->setClass('dbadmin-table-column-right')
            )->setClass("dbadmin-table-edit-row {$this->formColumnClass}"),
            // Second line
            $this->ui->row(
                $this->ui->col(
                    $this->getColumnCommentField($input, "{$editPrefix}[comment]",
                        "{$editPrefix}[setComment]", $this->trans->lang('Comment'), true)
                )->width(5)
                    ->setClass('dbadmin-table-column-left'),
                $this->ui->col(
                    $this->ui->row(
                        $this->ui->col(
                            $this->typeOptionComponent($input, $editPrefix)
                        )->width(4)
                            ->setClass('dbadmin-table-column-left'),
                        $this->ui->col(
                            $this->getColumnDefaultField($input, "{$editPrefix}[generated]",
                                "{$editPrefix}[default]", $this->trans->lang('Default value'))
                        )->width(8)
                            ->setClass('dbadmin-table-column-right')
                    )->addClass('nested-row')
                )->width(7)
                    ->setClass('dbadmin-table-column-right'),
            )->setClass("dbadmin-table-edit-row {$this->formColumnClass} $columnToggleClass")
                ->setStyle('display: none;')
        )->setClass('dbadmin-table-edit-column')
            ->setId($this->columnDivId($columnId))
            ->setStyle($this->getColumnBgColor($input));
    }

    /**
     * @param ColumnFormDto $input
     *
     * @return mixed
     */
    private function getForeignKeyBgColor(ColumnFormDto $input): string
    {
        return match(true) {
            $input->fkAdded() => "background-color: #e6ffe6;",
            $input->fkEdited() => "background-color: #d9f1ff;",
            $input->fkDropped() => "background-color: #ffe6e6;",
            default => "background-color: white;",
        };
    }

    /**
     * @param ColumnFormDto $input
     *
     * @return Component
     */
    protected function foreignKeyUiInput(ColumnFormDto $input): Component
    {
        $editPrefix = sprintf("columns[%d]", $input->position);

        return $this->ui->div(
            $this->ui->row(
                $this->ui->col(
                    $this->ui->inputGroup(
                        $this->ui->label('Foreign key')
                            ->setStyle('width: 30%;'),
                        $this->getColumnForeignKeyInput($input, "{$editPrefix}[foreignKey]")
                            ->setStyle('width: 70%;')
                            ->with(fn($elt) => $this->disable($elt))
                    )
                )->width(5)
                    ->setClass('dbadmin-table-column-left'),
                $this->ui->col(
                    $this->ui->row(
                        $this->ui->col(
                            $this->ui->input('')
                                ->setValue($input->foreignKey?->name ?? '')
                                ->with(fn($elt) => $this->disable($elt)),
                        )->width(5)
                            ->setClass('dbadmin-table-column-left'),
                        $this->ui->col(
                            $this->getForeignKeyOnUpdateField($input, "{$editPrefix}[fkOnUpdate]")
                                ->with(fn($elt) => $this->disable($elt))
                        )->width(3)
                            ->setClass('dbadmin-table-column-middle'),
                        $this->ui->col(
                            $this->getForeignKeyOnDeleteField($input, "{$editPrefix}[fkOnDelete]")
                                ->with(fn($elt) => $this->disable($elt))
                        )->width(3)
                            ->setClass('dbadmin-table-column-right'),
                        // $this->ui->col(
                        //     $this->ui->inputGroup(
                        //         $this->getForeignKeyDeferrableField($input, "{$editPrefix}[fkDeferrable]")
                        //             ->with(fn($elt) => $this->disable($elt, false))
                        //     )
                        // )->width(3)
                        //     ->setClass('dbadmin-table-column-right')
                    )->addClass('nested-row')
                )->width(7)
                    ->setClass('dbadmin-table-column-right'),
            )->setClass('dbadmin-table-edit-row')
        )->setClass('dbadmin-table-edit-foreign-key')
            ->setStyle($this->getForeignKeyBgColor($input));
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
                    $this->ui->list(
                        $this->columnUiInput($input, $columnId),
                        $this->ui->when($input->hasForeignKey(), fn() =>
                            $this->foreignKeyUiInput($input)
                        )
                    )
                )
            )
        );
    }
}
