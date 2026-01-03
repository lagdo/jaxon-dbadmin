<?php

namespace Lagdo\DbAdmin\Ui\Table;

use Jaxon\Script\Call\JxnCall;
use Lagdo\DbAdmin\Ajax\Admin\Db\Table\Ddl\AlterFunc;
use Lagdo\DbAdmin\Ajax\Admin\Db\Table\Ddl\Column;
use Lagdo\DbAdmin\Db\Page\Ddl\ColumnEntity;
use Lagdo\DbAdmin\Db\Translator;
use Lagdo\DbAdmin\Ui\PageTrait;
use Lagdo\UiBuilder\BuilderInterface;

use function count;
use function Jaxon\je;
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
     */
    public function __construct(protected Translator $trans, protected BuilderInterface $ui)
    {}

    /**
     * @var JxnCall
     */
    private function rqAlter(): JxnCall
    {
        return $this->rq['alter'] ??= rq(AlterFunc::class);
    }

    /**
     * @var JxnCall
     */
    private function rqTable(): JxnCall
    {
        return $this->rq['table'] ??= rq(Column\Table::class);
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
     * @param array $columns
     *
     * @return self
     */
    public function columns(array $columns): self
    {
        $this->columns = $columns;
        return $this;
    }

    /**
     * @return mixed
     */
    protected function headerNameRow(): mixed
    {
        return $this->ui->formRow(
            $this->ui->formCol(
                $this->ui->label($this->ui->text('Table'))
                    ->setStyle('margin-left: 5px;')
            )->width(2)
        )->setClass('dbadmin-edit-table-header');
    }

    /**
     * @return mixed
     */
    protected function headerEditRow(): mixed
    {
        $hasEngines = count($this->engines) > 0;
        $hasCollations = count($this->collations) > 0;

        return $this->ui->div(
            $this->ui->formRow(
                $this->ui->formCol(
                    $this->ui->formInput()
                        ->setType('text')->setName('name')
                        ->setValue($this->table['name'] ?? '')->setPlaceholder('Name')
                )->width(4)
                    ->setClass('dbadmin-table-column-left'),
                $this->ui->formCol(
                    $this->ui->label($this->ui->html('&nbsp'))
                )->width(1)
                    ->setClass('dbadmin-table-column-middle'),
                $this->ui->when($hasCollations, fn() =>
                    $this->ui->formCol(
                        $this->getCollationSelect($this->table['collation'] ?? '')
                            ->setName('collation')
                    )->width(4)
                        ->setClass('dbadmin-edit-table-collation')
                ),
                $this->ui->when($hasEngines, fn() =>
                    $this->ui->formCol(
                        $this->getEngineSelect($this->table['engine'] ?? '')
                            ->setName('engine')
                    )->width(3)
                        ->setClass('dbadmin-edit-table-engine')
                ),
                $this->ui->when($hasEngines || $hasCollations, fn() =>
                    $this->ui->formCol(
                        $this->ui->label($this->ui->html('&nbsp'))
                    )->width(5)
                        ->setClass('dbadmin-table-column-middle')
                ),
                $this->ui->when(isset($this->support['comment']), fn() =>
                    $this->ui->formCol(
                        $this->ui->formInput()
                            ->setType('text')
                            ->setName('comment')
                            ->setValue($this->table['comment'] ?? '')
                            ->setPlaceholder($this->trans->lang('Comment'))
                    )->width(6)
                        ->setClass('dbadmin-table-column-right')
                )
            )->setClass('dbadmin-table-edit-field'),
        );
    }

    /**
     * @return mixed
     */
    protected function headerColumnRow(): mixed
    {
        return $this->ui->formRow(
            $this->ui->formCol(
                $this->ui->label($this->ui->text($this->trans->lang('Column')))
                    ->setStyle('margin-left: 5px;')
            )->width(4),
            $this->ui->formCol(
                $this->ui->html('&nbsp;')
            )->width(1),
            $this->ui->formCol(
                $this->ui->label($this->ui->text($this->trans->lang('Options')))
                    ->setStyle('margin-left: -10px;')
            )->width(4),
            $this->ui->formCol(
                $this->ui->buttonGroup(
                    $this->ui->when($this->support['columns'], fn() =>
                        $this->ui->button($this->trans->lang('Add'))
                            ->primary()
                            ->jxnClick($this->rqCreate()->add())
                    ),
                    $this->ui->button($this->trans->lang('Changes'))
                        ->secondary()
                        ->jxnClick($this->rqAlter()->changes(je($this->formId)->rd()->form()))
                )
            )->width(3)
        )->setClass('dbadmin-table-column-header');
    }

    /**
     * @return string
     */
    public function wrapper(): string
    {
        return $this->ui->build(
            $this->ui->div(
                $this->ui->form(
                    $this->headerNameRow(),
                    $this->headerEditRow(),
                    $this->headerColumnRow()
                )->wrapped(false)->setId($this->formId)
            ),
            $this->ui->form(
                $this->ui->div()->jxnBind($this->rqTable())
            )->wrapped(false)
        );
    }

    /**
     * @param ColumnEntity $column
     *
     * @return mixed
     */
    protected function getColumnActionMenu(ColumnEntity $column): mixed
    {
        $movableUp = $this->support['move_col'] && $column->position > 0;
        $movableDown = $this->support['move_col'] &&
            $column->position < count($this->columns) - 1;
        $cancelQuestion = 'Confirm the cancellation?';
        $isNew = $column->status === 'added';
        $removable = $isNew || $this->support['drop_col'];
        $removeText = $isNew ? 'Cancel' : 'Remove';
        $removeQuestion = $isNew ? 'Remove this new colum?' :
            "Remove the \"{$column->name}\" column?";

        return $this->ui->dropdown(
            $this->ui->dropdownItem()->look('primary')/*->addCaret()*/,
            $this->ui->dropdownMenu(
                $this->ui->when($column->status !== 'deleted', fn() =>
                    $this->ui->list(
                        $this->ui->when($movableUp, fn() =>
                            $this->ui->dropdownMenuItem($this->ui->text('Up'))
                                ->jxnClick($this->rqMove()->up($this->formValues(), $column->position))
                        ),
                        $this->ui->when($movableDown, fn() =>
                            $this->ui->dropdownMenuItem($this->ui->text('Down'))
                                ->jxnClick($this->rqMove()->down($this->formValues(), $column->position))
                        ),
                        $this->ui->dropdownMenuItem($this->ui->text('Add'))
                            ->jxnClick($this->rqCreate()->add($column->position)),
                        $this->ui->dropdownMenuItem($this->ui->text('Edit'))
                            ->jxnClick($this->rqUpdate()->edit($column->name)),
                        $this->ui->when($column->status === 'edited', fn() =>
                            $this->ui->dropdownMenuItem($this->ui->text('Cancel'))
                                ->jxnClick($this->rqUpdate()->cancel($column->name)->confirm($cancelQuestion))
                        ),
                        $this->ui->when($removable, fn() =>
                            $this->ui->dropdownMenuItem($this->ui->text($removeText))
                                ->jxnClick($this->rqDelete()->exec($column->name)->confirm($removeQuestion))
                        )
                    )
                ),
                $this->ui->when($column->status === 'deleted', fn() =>
                    $this->ui->dropdownMenuItem($this->ui->text('Cancel'))
                        ->jxnClick($this->rqDelete()->cancel($column->name)->confirm($cancelQuestion))
                )
            )
        )->setClass('dbadmin-table-column-buttons');
    }

    /**
     * @param ColumnEntity $column
     *
     * @return mixed
     */
    private function getColumnBgColor(ColumnEntity $column): string
    {
        return match($column->status) {
            'added' => "background-color: #e6ffe6;",
            'edited' => "background-color: #d9f1ffff;",
            'deleted' => "background-color: #ffe6e6;",
            default => "background-color: white;",
        };
    }

    /**
     * @param ColumnEntity $column
     *
     * @return mixed
     */
    protected function columnElement(ColumnEntity $column): mixed
    {
        $editPrefix = sprintf("fields[%d]", $column->position);

        return $this->ui->formRow(
            // First line
            $this->ui->col(
                $this->getColumnNameField($column, "{$editPrefix}[name]")
                    ->setPlaceholder($this->trans->lang('Name'))
                    ->with(fn($input) => $this->disable($input))
            )->width(4)
                ->setClass('dbadmin-table-column-left'),
            $this->ui->col(
                $this->getColumnPrimaryField($column, "{$editPrefix}[primary]")
                    ->with(fn($input) => $this->disable($input, false)),
                $this->ui->span($this->ui->html('&nbsp;Primary'))
            )->width(1)
                ->setClass('dbadmin-table-column-middle')
                ->setStyle('padding-top: 7px'),
            $this->ui->formCol(
                $this->ui->formRow(
                    $this->ui->col(
                            $this->getColumnCommentField($column, "{$editPrefix}[comment]")
                                ->setPlaceholder($this->trans->lang('Comment'))
                                ->with(fn($input) => $this->disable($input))
                    )->width(11)
                        ->setClass('dbadmin-table-column-middle nested-col'),
                    $this->ui->col(
                        $this->getColumnActionMenu($column)
                    )->width(1)
                        ->setClass('dbadmin-table-column-buttons nested-col')
                )->setClass('nested-row')
            )->width(7),

            // Second line
            $this->ui->formCol(
                $this->ui->formRow(
                    $this->ui->col(
                            $this->getColumnTypeField($column, "{$editPrefix}[type]")
                                ->with(fn($input) => $this->disable($input))
                    )->width(8)
                        ->setClass('dbadmin-table-column-left nested-col'),
                    $this->ui->col(
                        $this->getColumnLengthField($column, "{$editPrefix}[length]")
                            ->with(fn($input) => $this->disable($input))
                    )->width(4)
                        ->setClass('dbadmin-table-column-right nested-col'),
                )->setClass('nested-row')
            )->width(4)
                ->setClass('second-line'),
            $this->ui->col(
                $this->getColumnAutoIncrementField($column, "{$editPrefix}[autoIncrement]")
                    ->with(fn($input) => $this->disable($input, false)),
                $this->ui->span($this->ui->html('&nbsp;AI&nbsp;')),
                $this->getColumnNullableField($column, "{$editPrefix}[null]")
                    ->with(fn($input) => $this->disable($input, false)),
                $this->ui->span($this->ui->html('&nbsp;N'))
            )->width(1)
                ->setClass('dbadmin-table-column-middle second-line')
                ->setStyle('padding-top: 7px'),
            $this->ui->col(
                $this->getColumnCollationField($column, "{$editPrefix}[collation]")
                    ->with(fn($input) => $this->disable($input))
            )->width(4)
                ->setClass('dbadmin-table-column-middle second-line'),
            $this->ui->col(
                $this->getColumnOnUpdateField($column, "{$editPrefix}[onUpdate]")
                    ->with(fn($input) => $this->disable($input))
            )->width(3)
                ->setClass('dbadmin-table-column-right second-line'),

            // Third line
            $this->ui->col(
                $this->getColumnDefaultField($column, "{$editPrefix}[hasDefault]",
                "{$editPrefix}[default]", $this->trans->lang('Default value'))
            )->width(5)
                ->setClass('dbadmin-table-column-left second-line'),
            $this->ui->col(
                $this->getColumnUnsignedField($column, "{$editPrefix}[unsigned]")
                    ->with(fn($input) => $this->disable($input))
            )->width(4)
                ->setClass('dbadmin-table-column-middle second-line'),
            $this->ui->col(
                $this->getColumnOnDeleteField($column, "{$editPrefix}[onDelete]")
                    ->with(fn($input) => $this->disable($input))
            )->width(3)
                ->setClass('dbadmin-table-column-right second-line'),
        )->setClass("{$this->formId}-column");
    }

    /**
     * @return string
     */
    public function showColumns(): string
    {
        $this->listMode = true;

        return $this->ui->build(
            $this->ui->each($this->columns, fn($column) =>
                $this->ui->div(
                    $this->columnElement($column)
                )->setClass('dbadmin-table-edit-field')
                    ->setStyle($this->getColumnBgColor($column))
            )
        );
    }
}
