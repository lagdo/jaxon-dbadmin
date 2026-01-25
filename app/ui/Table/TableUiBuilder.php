<?php

namespace Lagdo\DbAdmin\Ui\Table;

use Jaxon\Script\Call\JxnCall;
use Lagdo\DbAdmin\Ajax\Admin\Db\Table\Ddl\Column;
use Lagdo\DbAdmin\Db\UiData\Ddl\ColumnInputDto;
use Lagdo\DbAdmin\Db\Translator;
use Lagdo\DbAdmin\Ui\PageTrait;
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
     */
    public function __construct(protected Translator $trans, protected BuilderInterface $ui)
    {}

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
        $support = $this->support();

        return $this->ui->div(
            $this->ui->row(
                $this->ui->col(
                    $this->ui->row(
                        $this->ui->col(
                            $this->ui->input()
                                ->setType('text')
                                ->setName('name')
                                ->setValue($this->table['name'] ?? '')
                                ->setPlaceholder('Name')
                        )->width(12)
                    ),
                    $this->ui->row(
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
                        )->width(7)
                    )
                )->width(4),
                $this->ui->col($this->ui->html('&nbsp'))->width(1),
                $this->ui->col(
                    $this->ui->when($hasEngines || $hasCollations, fn() =>
                        $this->ui->row(
                            $this->ui->when($hasCollations, fn() =>
                                $this->ui->col(
                                    $this->getCollationSelect($this->table['collation'] ?? '')
                                        ->setName('collation')
                                )->width(6)
                            ),
                            $this->ui->when($hasEngines, fn() =>
                                $this->ui->col(
                                    $this->getEngineSelect($this->table['engine'] ?? '')
                                        ->setName('engine')
                                )->width(4)
                            )
                        )
                    ),
                    $this->ui->when(isset($support['comment']), fn() =>
                        $this->ui->row(
                            $this->ui->col(
                                $this->ui->input()
                                    ->setType('text')
                                    ->setName('comment')
                                    ->setValue($this->table['comment'] ?? '')
                                    ->setPlaceholder($this->trans->lang('Comment'))
                            )->width(11)
                        )
                    )
                )->width(7)
            )->setClass('dbadmin-table-edit-field'),
        );
    }

    /**
     * @return mixed
     */
    protected function columnsHeaderBlock(): mixed
    {
        $support = $this->support();
        return $this->ui->row(
            $this->ui->col(
                $this->ui->label($this->ui->text($this->trans->lang('Column')))
                    ->setStyle('margin-left: 5px;')
            )->width(4),
            $this->ui->col(
                $this->ui->html('&nbsp;')
            )->width(1),
            $this->ui->col(
                $this->ui->label($this->ui->text($this->trans->lang('Options')))
                    ->setStyle('margin-left: -10px;')
            )->width(4),
            $this->ui->col(
                $this->ui->when($support['columns'], fn() =>
                    $this->ui->button($this->trans->lang('Add'))
                        ->primary()
                        ->jxnClick($this->rqCreate()->add())
                )
            )->width(3)
                ->setAlign('right')
                ->setStyle('padding-right: 30px;')
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
                    $this->tableNameBlock(),
                    $this->tablePropertiesForm(),
                    $this->columnsHeaderBlock()
                )->wrapped(false)->setId($this->listFormId())
            ),
            $this->ui->form(
                $this->ui->div()->tbnBind($this->rqTable())
            )->wrapped(false)
        );
    }

    /**
     * @param ColumnInputDto $column
     * @param string $columnId
     *
     * @return mixed
     */
    protected function getColumnActionMenu(ColumnInputDto $column, string $columnId): mixed
    {
        $support = $this->support();
        $movableUp = $support['move_col'] && $column->position > 0;
        $movableDown = $support['move_col'] &&
            $column->position < count($this->columns) - 1;
        $cancelQuestion = 'Confirm the cancellation?';
        $isAdded = $column->added();
        $removable = $isAdded || $support['drop_col'];
        $removeText = $isAdded ? 'Cancel' : 'Remove';
        $removeQuestion = $isAdded ? 'Remove this new colum?' :
            "Remove the \"{$column->name}\" column?";

        return $this->ui->dropdown(
            $this->ui->dropdownItem()->look('primary')/*->addCaret()*/,
            $this->ui->dropdownMenu(
                $this->ui->when(!$column->dropped(), fn() =>
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
                        $this->ui->when($column->changed(), fn() =>
                            $this->ui->dropdownMenuItem($this->ui->text('Cancel'))
                                ->jxnClick($this->rqUpdate()->cancel($columnId)->confirm($cancelQuestion))
                        ),
                        $this->ui->when($removable, fn() =>
                            $this->ui->dropdownMenuItem($this->ui->text($removeText))
                                ->jxnClick($this->rqDelete()->exec($columnId)->confirm($removeQuestion))
                        )
                    )
                ),
                $this->ui->when($column->dropped(), fn() =>
                    $this->ui->dropdownMenuItem($this->ui->text('Cancel'))
                        ->jxnClick($this->rqDelete()->cancel($columnId)->confirm($cancelQuestion))
                )
            )
        )->setClass('dbadmin-table-column-buttons');
    }

    /**
     * @param ColumnInputDto $column
     *
     * @return mixed
     */
    private function getColumnBgColor(ColumnInputDto $column): string
    {
        return match(true) {
            $column->added() => "background-color: #e6ffe6;",
            $column->changed() => "background-color: #d9f1ffff;",
            $column->dropped() => "background-color: #ffe6e6;",
            default => "background-color: white;",
        };
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
     * @param ColumnInputDto $column
     * @param string $columnId
     *
     * @return mixed
     */
    protected function columnElement(ColumnInputDto $column, string $columnId): mixed
    {
        $editPrefix = sprintf("fields[%d]", $column->position);
        $support = $this->support();

        return $this->ui->row(
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
            $this->ui->col(
                $this->ui->row(
                    $this->ui->col(
                        $this->ui->when(isset($support['comment']), fn() =>
                            $this->getColumnCommentField($column, "{$editPrefix}[comment]")
                                ->setPlaceholder($this->trans->lang('Comment'))
                                ->with(fn($input) => $this->disable($input))
                        )
                    )->width(11)
                        ->setClass('dbadmin-table-column-middle nested-col'),
                    $this->ui->col(
                        $this->getColumnActionMenu($column, $columnId)
                    )->width(1)
                        ->setClass('dbadmin-table-column-buttons nested-col')
                )->setClass('nested-row')
            )->width(7),

            // Second line
            $this->ui->col(
                $this->ui->row(
                    $this->ui->col(
                        $this->getColumnTypeField($column, "{$editPrefix}[type]")
                            ->with(fn($input) => $this->disable($input))
                    )->width(8)
                        ->setClass('dbadmin-table-column-left nested-col'),
                    $this->ui->col(
                        $this->ui->when($column->field->lengthRequired, fn() =>
                            $this->getColumnLengthField($column, "{$editPrefix}[length]")
                                ->with(fn($input) => $this->disable($input)))
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
                $column->field->collationHidden ?
                    $this->hiddenField('Collation') :
                    $this->getColumnCollationField($column, "{$editPrefix}[collation]")
                        ->with(fn($input) => $this->disable($input))
            )->width(4)
                ->setClass('dbadmin-table-column-middle second-line'),
            $this->ui->col(
                $column->field->onUpdateHidden ?
                    $this->hiddenField('On update') :
                    $this->getColumnOnUpdateField($column, "{$editPrefix}[onUpdate]")
                        ->with(fn($input) => $this->disable($input))
            )->width(3)
                ->setClass('dbadmin-table-column-right second-line'),

            // Third line
            $this->ui->col(
                $this->getColumnDefaultField($column, "{$editPrefix}[generated]",
                "{$editPrefix}[default]", $this->trans->lang('Default value'))
            )->width(5)
                ->setClass('dbadmin-table-column-left second-line'),
            $this->ui->col(
                $column->field->unsignedHidden ?
                    $this->hiddenField('Unsigned') :
                    $this->getColumnUnsignedField($column, "{$editPrefix}[unsigned]")
                        ->with(fn($input) => $this->disable($input))
            )->width(4)
                ->setClass('dbadmin-table-column-middle second-line'),
            $this->ui->col(
                $column->field->onDeleteHidden ?
                $this->hiddenField('On delete') :
                $this->getColumnOnDeleteField($column, "{$editPrefix}[onDelete]")
                    ->with(fn($input) => $this->disable($input))
            )->width(3)
                ->setClass('dbadmin-table-column-right second-line'),
        )->setClass($this->formColumnClass);
    }

    /**
     * @return string
     */
    public function showColumns(): string
    {
        $this->listMode = true;

        return $this->ui->build(
            $this->ui->form(
                $this->ui->each($this->columns, fn($column, $columnId) =>
                    $this->ui->div(
                        $this->columnElement($column, $columnId)
                    )->setClass('dbadmin-table-edit-field')
                        ->setStyle($this->getColumnBgColor($column))
                )
            )
        );
    }
}
