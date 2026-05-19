<?php

namespace Lagdo\DbAdmin\App\Ui\Table;

use Jaxon\Script\Call\JxnCall;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Ddl\Column;
use Lagdo\DbAdmin\App\Ui\PageTrait;
use Lagdo\DbAdmin\App\Ui\Tab\Tab;
use Lagdo\DbAdmin\App\Ui\Table\Column\ColumnTrait;
use Lagdo\DbAdmin\Driver\Sql\Dto\TableDto;
use Lagdo\DbAdmin\Support\Driver\DriverProxy;
use Lagdo\DbAdmin\Support\Driver\UiDto\Ddl\ColumnFormDto;
use Lagdo\DbAdmin\Support\Translator;
use Lagdo\UiBuilder\BuilderInterface;
use Closure;

use function array_filter;
use function array_values;
use function count;
use function Jaxon\rq;
use function sprintf;

class TableUiBuilder
{
    use PageTrait;
    use TableTrait;
    use ColumnTrait;

    /**
     * @var array<JxnCall>
     */
    private array $rq = [];

    /**
     * @var Closure
     */
    private Closure $dbGetter;

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
     * @param Closure $dbGetter
     *
     * @return self
     */
    public function dbGetter(Closure $dbGetter): self
    {
        $this->dbGetter = $dbGetter;
        return $this;
    }

    /**
     * @return DriverProxy
     */
    protected function db(): DriverProxy
    {
        return ($this->dbGetter)();
    }

    /**
     * @param array<ColumnFormDto> $inputs
     *
     * @return self
     */
    public function inputs(array $inputs): self
    {
        $this->inputs = $inputs;
        return $this;
    }

    /**
     * @return string
     */
    public function wrapperTitle(): string
    {
        $support = $this->support(['columns']);
        return $this->ui->build(
            $this->ui->div(
                $this->ui->div(
                    $this->trans->lang('Columns')
                )->setStyle('flex: 1'),
                $this->ui->div(
                    $this->ui->when($support['columns'], fn() =>
                        $this->ui->button($this->ui->html('<i class="fa fa-plus"></i>'))
                            ->primary()
                            ->small()
                            ->jxnClick($this->rqCreate()->add())
                    )
                )->setStyle('width:50px; padding-left:5px;')
            )->setStyle('display:flex; flex-direction:row; align-items:flex-start;')
        );
    }

    /**
     * @return TableDto|null
     */
    private function getEditedTable(): TableDto|null
    {
        return $this->metadata['table'] ?? null;;
    }

    /**
     * @return array
     */
    private function autoIncrementIsEditable(): array
    {
        $primaryKeyFilter = fn(ColumnFormDto $input) => $input->values()->primary &&
            $this->db()->typeIsAutoIncrementable($input->values()->type);
        $primaryKeyInputs = array_filter($this->inputs, $primaryKeyFilter);

        $autoIncrementFilter = fn(ColumnFormDto $input) => $input->values()->autoIncrement;
        $autoIncrementInputs = array_values(array_filter($this->inputs, $autoIncrementFilter));
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
        $table = $this->getEditedTable();

        [$autoIncrementColumn, $aiEditable] = $this->autoIncrementIsEditable();

        return $this->ui->build(
            $this->ui->form(
                $this->ui->list(
                    $this->ui->row(
                        $this->ui->col(
                            $this->ui->input()
                                ->setType('text')
                                ->setName('name')
                                ->setValue($formValues['name'] ?? $table?->name ?? '')
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
                                    ->setValue($formValues['autoIncrement'] ?? $table?->autoIncrementValue ?? '')
                                    ->when(!$aiEditable, fn($input) => $this->disable($input, true))
                            )
                        )->setClass('dbadmin-table-column-middle')
                            ->width(3),
                        $this->ui->col(
                            $this->ui->when($support['comment'], fn() =>
                                $this->ui->inputGroup(
                                    $this->ui->checkbox()
                                        ->checked($formValues['setComment'] ?? false)
                                        ->setValue('1')
                                        ->setName('setComment'),
                                    $this->ui->input()
                                        ->setType('text')
                                        ->setName('comment')
                                        ->setValue($formValues['comment'] ?? $table?->comment ?? '')
                                        ->setPlaceholder($this->trans->lang('Comment'))
                                )
                            )
                        )->setClass('dbadmin-table-column-right')
                            ->width(6)
                    )->setClass('dbadmin-table-edit-row'),
                    $this->ui->when($hasCollations || $hasEngines, fn() =>
                        $this->ui->row(
                            $this->ui->when($hasCollations, fn() =>
                                $this->ui->col(
                                    $this->getCollationSelect($formValues['collation'] ?? $table?->collation ?? '')
                                        ->setName('collation')
                                )->setClass('dbadmin-table-column-left')
                                    ->width(3)
                            ),
                            $this->ui->when($hasEngines, fn() =>
                                $this->ui->col(
                                    $this->getEngineSelect($formValues['engine'] ?? $table?->engine ?? '')
                                        ->setName('engine')
                                )->setClass(!$hasCollations ? 'dbadmin-table-column-left' :
                                    'dbadmin-table-column-middle')
                                    ->width(3)
                            )
                        )->setClass('dbadmin-table-edit-row')
                    ),
                )
            )->setId($this->tableFormId())
        );
    }

    /**
     * @return string
     */
    public function header(): string
    {
        return $this->ui->build(
            $this->ui->div()
                ->setClass('dbadmin-table-edit-column')
                ->tbnBindApp($this->rqHeader())
        );
    }

    /**
     * @return string
     */
    public function wrapper(): string
    {
        return $this->ui->build(
            $this->ui->form(
                $this->ui->div()->tbnBindApp($this->rqWrapper())
            )->setId($this->columnsFormId())
        );
    }

    /**
     * @param ColumnFormDto $input
     * @param string $columnId
     *
     * @return mixed
     */
    protected function getColumnActionMenu(ColumnFormDto $input, string $columnId): mixed
    {
        $support = $this->support(['move_col', 'drop_col']);
        $movableUp = $support['move_col'] && $input->position > 0;
        $movableDown = $support['move_col'] &&
            $input->position < count($this->inputs) - 1;
        $cancelQuestion = 'Confirm the cancellation?';
        $isAdded = $input->added();
        $removable = $isAdded || $support['drop_col'];
        $removeText = $isAdded ? 'Cancel' : 'Remove';
        $removeQuestion = $isAdded ? 'Remove this new colum?' :
            "Remove the \"{$input->column->name}\" column?";

        return $this->ui->dropdown(
            $this->ui->dropdownItem()->look('primary')/*->addCaret()*/,
            $this->ui->dropdownMenu(
                $this->ui->when(!$input->dropped(), fn() =>
                    $this->ui->list(
                        $this->ui->when($movableUp, fn() =>
                            $this->ui->dropdownMenuItem($this->ui->text('Up'))
                                ->jxnClick($this->rqMove()->up($columnId))
                        ),
                        $this->ui->when($movableDown, fn() =>
                            $this->ui->dropdownMenuItem($this->ui->text('Down'))
                                ->jxnClick($this->rqMove()->down($columnId))
                        ),
                        $this->ui->dropdownMenuItem($this->ui->text('Add'))
                            ->jxnClick($this->rqCreate()->add($columnId)),
                        $this->ui->dropdownMenuItem($this->ui->text('Edit'))
                            ->jxnClick($this->rqUpdate()->edit($columnId)),
                        $this->ui->when($input->changed(), fn() =>
                            $this->ui->dropdownMenuItem($this->ui->text('Cancel'))
                                ->jxnClick($this->rqUpdate()->cancel($columnId)->confirm($cancelQuestion))
                        ),
                        $this->ui->when($removable, fn() =>
                            $this->ui->dropdownMenuItem($this->ui->text($removeText))
                                ->jxnClick($this->rqDelete()->exec($columnId)->confirm($removeQuestion))
                        )
                    )
                ),
                $this->ui->when($input->dropped(), fn() =>
                    $this->ui->dropdownMenuItem($this->ui->text('Cancel'))
                        ->jxnClick($this->rqDelete()->cancel($columnId)->confirm($cancelQuestion))
                )
            )
        )->setClass('dbadmin-table-column-buttons');
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

        return $this->ui->list(
            // First line
            $this->ui->row(
                $this->ui->col(
                    $this->getColumnNameField($input, "{$editPrefix}[name]")
                        ->setPlaceholder($this->trans->lang('Name'))
                        ->with(fn($input) => $this->disable($input, true))
                )->width(3)
                    ->setClass('dbadmin-table-column-left'),
                $this->ui->col(
                    $this->ui->row(
                        $this->ui->col(
                            $this->ui->inputGroup(
                                $this->ui->input('')
                                    ->setPlaceholder('Primary')
                                    ->with(fn($input) => $this->disable($input, true)),
                                $this->getColumnPrimaryField($input, "{$editPrefix}[primary]")
                                    ->with(fn($input) => $this->disable($input, false))
                            )
                        )->width(6)
                            ->setClass('dbadmin-table-column-left'),
                        $this->ui->col(
                            $this->ui->inputGroup(
                                $this->ui->input('')
                                    ->setPlaceholder('Auto Inc.')
                                    ->with(fn($input) => $this->disable($input, true)),
                                $this->getColumnAutoIncrementField($input, "{$editPrefix}[autoIncrement]")
                                    ->with(fn($input) => $this->disable($input, false))
                            )
                        )->width(6)
                            ->setClass('dbadmin-table-column-right')
                    )->addClass('nested-row')
                )->width(3)
                    ->setClass('dbadmin-table-column-middle'),
                $this->ui->col(
                    $this->ui->when($support['comment'], fn() =>
                        $this->getColumnCommentField($input, "{$editPrefix}[comment]",
                            "{$editPrefix}[setComment]", $this->trans->lang('Comment'), true)
                    )
                )->width(6)
                    ->setClass('dbadmin-table-column-right')
            )->setClass("dbadmin-table-edit-row {$this->formColumnClass}"),
            // Second line
            $this->ui->row(
                $this->ui->col(
                    $this->getColumnTypeField($input, "{$editPrefix}[type]")
                        ->with(fn($input) => $this->disable($input))
                )->width(3)
                    ->setClass('dbadmin-table-column-left'),
                $this->ui->col(
                    $this->ui->row(
                        $this->ui->col(
                            $this->getColumnLengthField($input, "{$editPrefix}[length]")
                                ->with(fn($input) => $this->disable($input))
                        )->width(6)
                            ->setClass('dbadmin-table-column-left'),
                        $this->ui->col(
                            $this->ui->inputGroup(
                                $this->ui->input('')
                                    ->setPlaceholder('Nullable')
                                    ->with(fn($input) => $this->disable($input, true)),
                                $this->getColumnNullableField($input, "{$editPrefix}[null]")
                                    ->with(fn($input) => $this->disable($input, false))
                            )
                        )->width(6)
                            ->setClass('dbadmin-table-column-right')
                    )->addClass('nested-row')
                )->width(3)
                    ->setClass('dbadmin-table-column-middle'),
                $this->ui->col(
                    $this->ui->div(
                        $this->ui->div(
                            $this->getColumnDefaultField($input, "{$editPrefix}[generated]",
                                "{$editPrefix}[default]", $this->trans->lang('Default value'))
                        )->setStyle('flex: 1'),
                        $this->ui->div(
                            $this->getColumnActionMenu($input, $columnId)
                        )->setStyle('width:40px; padding-left:5px;')
                    )->setStyle('display:flex; flex-direction:row; align-items:flex-start;')
                )->width(6)
                    ->setClass('dbadmin-table-column-right'),
            )->setClass("dbadmin-table-edit-row {$this->formColumnClass}"),
            // Third line
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
                    $this->ui->div(
                        $this->ui->div(
                            $this->ui->row(
                                $this->ui->col(
                                    $this->getColumnOnUpdateField($input, "{$editPrefix}[onUpdate]")
                                        ->with(fn($input) => $this->disable($input))
                                )->setClass('dbadmin-table-column-left')
                                    ->width(6),
                                $this->ui->col(
                                    $this->getColumnOnDeleteField($input, "{$editPrefix}[onDelete]")
                                        ->with(fn($input) => $this->disable($input))
                                )->setClass('dbadmin-table-column-right')
                                    ->width(6),
                            )->addClass('nested-row')
                        )->setStyle('flex: 1'),
                        $this->ui->div()
                            ->setStyle('width:40px; padding-left:5px;')
                    )->setStyle('display:flex; flex-direction:row; align-items:flex-start;')
                )->width(6)
                    ->setClass('dbadmin-table-column-right')
            )->setClass("dbadmin-table-edit-row {$this->formColumnClass}")
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
            $input->changed() => "background-color: #d9f1ffff;",
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

        return $this->ui->build(
            $this->ui->form(
                $this->ui->each($this->inputs, fn(ColumnFormDto $input, string $columnId) =>
                    $this->ui->div(
                        $this->columnUiInput($input, $columnId)
                    )->setClass('dbadmin-table-edit-column')
                        ->setStyle($this->getColumnBgColor($input))
                )
            )
        );
    }
}
