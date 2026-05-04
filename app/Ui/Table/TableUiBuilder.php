<?php

namespace Lagdo\DbAdmin\App\Ui\Table;

use Jaxon\Script\Call\JxnCall;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Ddl\Column;
use Lagdo\DbAdmin\App\Ui\PageTrait;
use Lagdo\DbAdmin\App\Ui\Tab\Tab;
use Lagdo\DbAdmin\Driver\Sql\Dto\TableDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\Ddl\DdInputDto;
use Lagdo\DbAdmin\Support\Translator;
use Lagdo\UiBuilder\BuilderInterface;
use Lagdo\UiBuilder\Component\HtmlComponent;

use function count;
use function Jaxon\rq;
use function sprintf;

class TableUiBuilder
{
    use PageTrait;
    use TableTrait;
    use TableFieldTrait;

    /**
     * @var array<JxnCall>
     */
    private $rq = [];

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
     * @param array<DdInputDto> $inputs
     *
     * @return self
     */
    public function inputs(array $inputs): self
    {
        $this->inputs = $inputs;
        return $this;
    }

    /**
     * @return mixed
     */
    protected function tableTitleBlock(): mixed
    {
        return $this->ui->div(
            $this->ui->label($this->ui->text($this->trans->lang('Table')))
        )->setClass('dbadmin-edit-table-header');
    }

    /**
     * @return mixed
     */
    protected function columnsTitleBlock(): mixed
    {
        $support = $this->support();

        return $this->ui->div(
            $this->ui->div(
                $this->ui->label($this->ui->text($this->trans->lang('Columns')))
            )->setStyle('flex: 1'),
            $this->ui->div(
                $this->ui->when($support['columns'], fn() =>
                    $this->ui->button($this->ui->html('<i class="fa fa-plus"></i>'))
                        ->primary()
                        ->jxnClick($this->rqCreate()->add())
                )
            )->setStyle('width:40px; padding-left:5px;')
        )->setStyle('display:flex; flex-direction:row; align-items:flex-start;');
    }

    /**
     * @return mixed
     */
    protected function tablePropertiesForm(): mixed
    {
        $hasEngines = count($this->engines()) > 0;
        $hasCollations = count($this->collations()) > 0;
        $support = $this->support();
        /** @var TableDto|null */
        $table = $this->metadata['table'] ?? null;

        return $this->ui->list(
            $this->ui->row(
                $this->ui->col(
                    $this->ui->input()
                        ->setType('text')
                        ->setName('name')
                        ->setValue($table?->name ?? '')
                        ->setPlaceholder('Name')
                )->setClass('dbadmin-table-column-left')
                    ->width(4),
                $this->ui->col()->setClass('dbadmin-table-column-middle')
                    ->width(1),
                $this->ui->col(
                    $this->ui->row(
                        $this->ui->when($hasCollations, fn() =>
                            $this->ui->col(
                                $this->getCollationSelect($table?->collation ?? '')
                                    ->setName('collation')
                            )->setClass('dbadmin-table-column-left')
                                ->width(6)
                        ),
                        $this->ui->when($hasEngines, fn() =>
                            $this->ui->col(
                                $this->getEngineSelect($table?->engine ?? '')
                                    ->setName('engine')
                            )->setClass('dbadmin-table-column-right')
                                ->width(4)
                        )
                    )->setClass('nested-row')
                )->setClass('dbadmin-table-column-right')
                    ->width(7),
            )->setClass('dbadmin-table-edit-row'),
            $this->ui->row(
                $this->ui->col(
                    $this->ui->inputGroup(
                        $this->ui->checkbox()
                            ->checked($table?->hasAutoIncrement ?? false)
                            ->setName('hasAutoIncrement'),
                        $this->ui->input()
                            ->setName('autoIncrement')
                            ->setPlaceholder('Auto Increment')
                            ->setValue($table?->hasAutoIncrement ? $table->autoIncrement : '')
                    )
                )->setClass('dbadmin-table-column-left')
                    ->width(2),
                $this->ui->col()->setClass('dbadmin-table-column-middle')
                    ->width(3),
                $this->ui->col(
                    $this->ui->when($support['comment'], fn() =>
                        $this->ui->inputGroup(
                            $this->ui->checkbox()
                                ->checked(false)
                                ->setName('setComment'),
                            $this->ui->input()
                                ->setType('text')
                                ->setName('comment')
                                ->setValue($table?->comment ?? '')
                                ->setPlaceholder($this->trans->lang('Comment'))
                        )
                    )
                )->setClass('dbadmin-table-column-right')
                    ->width(7)
            )->setClass('dbadmin-table-edit-row')
        );
    }

    /**
     * @return string
     */
    public function header(): string
    {
        return $this->ui->build(
            $this->ui->div(
                $this->tableTitleBlock()
            )->setClass('dbadmin-table-edit-column'),
            $this->ui->form(
                $this->ui->div(
                    $this->tablePropertiesForm()
                )->setClass('dbadmin-table-edit-column')
            )->wrapped(false)->setId($this->listFormId())
        );
    }

    /**
     * @return string
     */
    public function wrapper(): string
    {
        return $this->ui->build(
            $this->ui->div(
                $this->columnsTitleBlock()
            )->setClass('dbadmin-table-edit-column'),
            $this->ui->form(
                $this->ui->div()->tbnBindApp($this->rqWrapper())
            )->wrapped(false)
        );
    }

    /**
     * @param DdInputDto $input
     * @param string $columnId
     *
     * @return mixed
     */
    protected function getColumnActionMenu(DdInputDto $input, string $columnId): mixed
    {
        $support = $this->support();
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
                                ->jxnClick($this->rqMove()->up($columnId, $this->listFormValues()))
                        ),
                        $this->ui->when($movableDown, fn() =>
                            $this->ui->dropdownMenuItem($this->ui->text('Down'))
                                ->jxnClick($this->rqMove()->down($columnId, $this->listFormValues()))
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
     * @param string $label
     *
     * @return HtmlComponent
     */
    private function hiddenField(string $label): HtmlComponent
    {
        return $this->ui->div(
            $this->ui->text("$label (" . $this->trans->lang('hidden') .")")
        )->setStyle('padding-top:7px;padding-left:10px;');
    }

    /**
     * @param DdInputDto $input
     * @param string $columnId
     *
     * @return mixed
     */
    protected function columnUiInput(DdInputDto $input, string $columnId): mixed
    {
        $editPrefix = sprintf("columns[%d]", $input->position);
        $support = $this->support();

        return $this->ui->list(
            // First line
            $this->ui->row(
                $this->ui->col(
                    $this->getColumnNameField($input, "{$editPrefix}[name]")
                        ->setPlaceholder($this->trans->lang('Name'))
                        ->with(fn($input) => $this->disable($input, true))
                )->width(4)
                    ->setClass('dbadmin-table-column-left'),
                $this->ui->col(
                    $this->getColumnPrimaryField($input, "{$editPrefix}[primary]")
                        ->with(fn($input) => $this->disable($input, false)),
                    $this->ui->span($this->ui->html('&nbsp;Primary'))
                )->width(1)
                    ->setClass('dbadmin-table-column-middle'),
                $this->ui->col(
                    $this->ui->when($support['comment'], fn() =>
                        $this->getColumnCommentField($input, "{$editPrefix}[comment]",
                            "{$editPrefix}[setComment]", $this->trans->lang('Comment'), true)
                    )
                )->width(7)
                    ->setClass('dbadmin-table-column-right')
            )->setClass("dbadmin-table-edit-row {$this->formColumnClass}"),
            // Second line
            $this->ui->row(
                $this->ui->col(
                    $this->ui->row(
                        $this->ui->col(
                            $this->getColumnTypeField($input, "{$editPrefix}[type]")
                                ->with(fn($input) => $this->disable($input))
                        )->width(8)
                            ->setClass('dbadmin-table-column-left'),
                        $this->ui->col(
                            $this->getColumnLengthField($input, "{$editPrefix}[length]")
                                ->with(fn($input) => $this->disable($input))
                        )->width(4)
                            ->setClass('dbadmin-table-column-right'),
                    )->addClass('nested-row')
                )->width(4)
                    ->setClass('dbadmin-table-column-left'),
                $this->ui->col(
                    $this->getColumnAutoIncrementField($input, "{$editPrefix}[autoIncrement]")
                        ->with(fn($input) => $this->disable($input, false)),
                    $this->ui->span($this->ui->html('&nbsp;AI&nbsp;')),
                    $this->getColumnNullableField($input, "{$editPrefix}[null]")
                        ->with(fn($input) => $this->disable($input, false)),
                    $this->ui->span($this->ui->html('&nbsp;N'))
                )->width(1)
                    ->setClass('dbadmin-table-column-middle'),
                $this->ui->col(
                    $this->ui->div(
                        $this->ui->div(
                            $this->ui->row(
                                $this->ui->col(
                                    $this->getColumnCollationField($input, "{$editPrefix}[collation]")
                                        ->with(fn($input) => $this->disable($input))
                                )->setClass('dbadmin-table-column-left')
                                    ->width(6),
                                $this->ui->col(
                                    $this->getColumnUnsignedField($input, "{$editPrefix}[unsigned]")
                                        ->with(fn($input) => $this->disable($input))
                                )->setClass('dbadmin-table-column-right')
                                    ->width(6),
                            )->addClass('nested-row')
                        )->setStyle('flex: 1'),
                        $this->ui->div(
                            $this->getColumnActionMenu($input, $columnId)
                        )->setStyle('width:40px; padding-left:5px;')
                    )->setStyle('display:flex; flex-direction:row; align-items:flex-start;')
                )->width(7)
                    ->setClass('dbadmin-table-column-right')
            )->setClass("dbadmin-table-edit-row {$this->formColumnClass}"),
            // Third line
            $this->ui->row(
                $this->ui->col(
                    $this->getColumnDefaultField($input, "{$editPrefix}[generated]",
                        "{$editPrefix}[default]", $this->trans->lang('Default value'))
                )->width(5)
                    ->setClass('dbadmin-table-column-left'),
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
                )->width(7)
                    ->setClass('dbadmin-table-column-right')
            )->setClass("dbadmin-table-edit-row {$this->formColumnClass}")
        );
    }

    /**
     * @param DdInputDto $input
     *
     * @return mixed
     */
    private function getColumnBgColor(DdInputDto $input): string
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
                $this->ui->each($this->inputs, fn($input, $columnId) =>
                    $this->ui->div(
                        $this->columnUiInput($input, $columnId)
                    )->setClass('dbadmin-table-edit-column')
                        ->setStyle($this->getColumnBgColor($input))
                )
            )
        );
    }
}
