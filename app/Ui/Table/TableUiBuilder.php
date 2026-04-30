<?php

namespace Lagdo\DbAdmin\App\Ui\Table;

use Jaxon\Script\Call\JxnCall;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Ddl\Column;
use Lagdo\DbAdmin\Support\Driver\UiDto\Ddl\DdInputDto;
use Lagdo\DbAdmin\Support\Translator;
use Lagdo\DbAdmin\App\Ui\PageTrait;
use Lagdo\DbAdmin\App\Ui\Tab\Tab;
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
     * @param array $table
     *
     * @return self
     */
    public function table(array $table): self
    {
        $this->table = $table;
        return $this;
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
    protected function tableNameBlock(): mixed
    {
        return $this->ui->row(
            $this->ui->col(
                $this->ui->label($this->ui->text('Table'))
                    ->setStyle('margin-left: 5px;')
            )->width(2)
        )->setClass('dbadmin-edit-table-header');
    }

    /**
     * @return mixed
     */
    protected function tablePropertiesForm(): mixed
    {
        $hasEngines = count($this->engines()) > 0;
        $hasCollations = count($this->collations()) > 0;
        $hasAutoIncrement = $this->table['hasAutoIncrement'] ?? false;
        $comment = $this->table['comment'] ?? null;
        $support = $this->support();

        return $this->ui->div(
            $this->ui->row(
                $this->ui->col(
                    $this->ui->input()
                        ->setType('text')
                        ->setName('name')
                        ->setValue($this->table['name'] ?? '')
                        ->setPlaceholder('Name')
                )->setClass('dbadmin-table-column-left')
                    ->width(4),
                $this->ui->col()->setClass('dbadmin-table-column-middle')
                    ->width(1),
                $this->ui->col(
                    $this->ui->row(
                        $this->ui->when($hasCollations, fn() =>
                            $this->ui->col(
                                $this->getCollationSelect($this->table['collation'] ?? '')
                                    ->setName('collation')
                            )->setClass('nested-col')
                                ->width(6)
                        ),
                        $this->ui->when($hasEngines, fn() =>
                            $this->ui->col(
                                $this->getEngineSelect($this->table['engine'] ?? '')
                                    ->setName('engine')
                            )->setClass('nested-col')
                                ->width(4)
                        )
                    )->setClass('nested-row')
                )->setClass('dbadmin-table-column-right')
                    ->width(7),
                $this->ui->col(
                    $this->ui->inputGroup(
                        $this->ui->checkbox()
                            ->checked($hasAutoIncrement)
                            ->setName('hasAutoIncrement'),
                        $this->ui->input()
                            ->setName('autoIncrement')
                            ->setPlaceholder('Auto Increment')
                            ->setValue($hasAutoIncrement ? $this->table['autoIncrement'] : '')
                    )
                )->setClass('dbadmin-table-column-left')
                    ->width(2),
                $this->ui->col()->setClass('dbadmin-table-column-middle')
                    ->width(3),
                $this->ui->col(
                    $this->ui->div(
                        $this->ui->div(
                            $this->ui->when($support['comment'], fn() =>
                                $this->ui->inputGroup(
                                    $this->ui->checkbox()
                                        ->checked(false)
                                        ->setName('setComment'),
                                    $this->ui->input()
                                        ->setType('text')
                                        ->setName('comment')
                                        ->setValue($comment ?? '')
                                        ->setPlaceholder($this->trans->lang('Comment'))
                                )
                            )
                        )->setStyle('flex: 1'),
                        $this->ui->div(
                            $this->ui->when($support['columns'], fn() =>
                                $this->ui->button($this->ui->html('<i class="fa fa-plus"></i>'))
                                    ->primary()
                                    ->jxnClick($this->rqCreate()->add())
                            )
                        )->setStyle('width:40px; padding-left:5px;')
                    )->setStyle('display:flex; flex-direction:row; align-items:flex-start;'),
                )->setClass('dbadmin-table-column-right')
                    ->width(7)
            )->setClass('dbadmin-table-edit-column'),
        );
    }

    /**
     * @return string
     */
    public function wrapper(): string
    {
        return $this->ui->build(
            $this->ui->div(
                $this->ui->form(
                    $this->tableNameBlock(),
                    $this->tablePropertiesForm(),
                )->wrapped(false)->setId($this->listFormId())
            ),
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
            $this->ui->text($this->trans->lang('hidden') . " ($label)")
        )->setStyle('padding-top:7px;padding-left:10px;');
    }

    /**
     * @param DdInputDto $input
     * @param string $columnId
     *
     * @return mixed
     */
    protected function columnElement(DdInputDto $input, string $columnId): mixed
    {
        $editPrefix = sprintf("columns[%d]", $input->position);
        $support = $this->support();

        return $this->ui->row(
            // First line
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
                ->setClass('dbadmin-table-column-middle')
                ->setStyle('padding-top: 7px'),
            $this->ui->col(
                $this->ui->row(
                    $this->ui->col(
                        $input->column->collationHidden ?
                            $this->hiddenField('Collation') :
                            $this->getColumnCollationField($input, "{$editPrefix}[collation]")
                                ->with(fn($input) => $this->disable($input))
                    )->width(6)
                        ->setClass('dbadmin-table-column-middle'),
                    $this->ui->col(
                        $input->column->onUpdateHidden ?
                            $this->hiddenField('On update') :
                            $this->getColumnOnUpdateField($input, "{$editPrefix}[onUpdate]")
                                ->with(fn($input) => $this->disable($input))
                    )->width(6)
                        ->setClass('dbadmin-table-column-right'),
                )->setClass('nested-row')
            )->width(7),

            // Second line
            $this->ui->col(
                $this->ui->row(
                    $this->ui->col(
                        $this->getColumnTypeField($input, "{$editPrefix}[type]")
                            ->with(fn($input) => $this->disable($input))
                    )->width(8)
                        ->setClass('dbadmin-table-column-left nested-col'),
                    $this->ui->col(
                        $this->ui->when($input->column->lengthRequired, fn() =>
                            $this->getColumnLengthField($input, "{$editPrefix}[length]")
                                ->with(fn($input) => $this->disable($input)))
                    )->width(4)
                        ->setClass('dbadmin-table-column-right nested-col'),
                )->setClass('nested-row')
            )->width(4),
            $this->ui->col(
                $this->getColumnAutoIncrementField($input, "{$editPrefix}[autoIncrement]")
                    ->with(fn($input) => $this->disable($input, false)),
                $this->ui->span($this->ui->html('&nbsp;AI&nbsp;')),
                $this->getColumnNullableField($input, "{$editPrefix}[null]")
                    ->with(fn($input) => $this->disable($input, false)),
                $this->ui->span($this->ui->html('&nbsp;N'))
            )->width(1)
                ->setClass('dbadmin-table-column-middle')
                ->setStyle('padding-top: 7px'),
            $this->ui->col(
                $this->ui->row(
                    $this->ui->col(
                        $input->column->unsignedHidden ?
                            $this->hiddenField('Unsigned') :
                            $this->getColumnUnsignedField($input, "{$editPrefix}[unsigned]")
                                ->with(fn($input) => $this->disable($input))
                    )->width(6)
                        ->setClass('dbadmin-table-column-middle'),
                    $this->ui->col(
                        $input->column->onDeleteHidden ?
                        $this->hiddenField('On delete') :
                        $this->getColumnOnDeleteField($input, "{$editPrefix}[onDelete]")
                            ->with(fn($input) => $this->disable($input))
                    )->width(6)
                        ->setClass('dbadmin-table-column-right'),
                )->setClass('nested-row')
            )->width(7),

            // Third line
            $this->ui->col(
                $this->getColumnDefaultField($input, "{$editPrefix}[generated]",
                "{$editPrefix}[default]", $this->trans->lang('Default value'))
            )->width(5)
                ->setClass('dbadmin-table-column-left'),
            $this->ui->col(
                $this->ui->div(
                    $this->ui->div(
                        $this->ui->when($support['comment'], fn() =>
                            $this->getColumnCommentField($input, "{$editPrefix}[comment]",
                                "{$editPrefix}[setComment]", $this->trans->lang('Comment'), true)
                        )
                    )->setStyle('flex: 1'),
                    $this->ui->div(
                        $this->getColumnActionMenu($input, $columnId)
                    )->setStyle('width:40px; padding-left:5px;')
                )->setStyle('display:flex; flex-direction:row; align-items:flex-start;')
                    ->setClass('nested-col')
            )->setStyle('padding-left:2px; padding-right:2px;')
                ->width(7)
        )->setClass($this->formColumnClass);
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
                $this->ui->each($this->columns, fn($input, $columnId) =>
                    $this->ui->div(
                        $this->columnElement($input, $columnId)
                    )->setClass('dbadmin-table-edit-column')
                        ->setStyle($this->getColumnBgColor($input))
                )
            )
        );
    }
}
