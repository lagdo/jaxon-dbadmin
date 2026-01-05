<?php

namespace Lagdo\DbAdmin\Ui\Table;

use Lagdo\DbAdmin\Db\Page\Ddl\ColumnEntity;
use Lagdo\DbAdmin\Db\Translator;
use Lagdo\UiBuilder\BuilderInterface;

class ColumnUiBuilder
{
    use TableFieldTrait;

    /**
     * @param Translator $trans
     * @param BuilderInterface $ui
     */
    public function __construct(protected Translator $trans, protected BuilderInterface $ui)
    {}

    /**
     * @param ColumnEntity $field
     *
     * @return string
     */
    public function column(ColumnEntity $column): string
    {
        $this->listMode = false;

        return $this->ui->build(
            $this->ui->form(
                ['id' => $this->formId],
                $this->ui->row(
                    $this->ui->col(
                        $this->ui->text($this->trans->lang('Name'))
                    )->width(3),
                    $this->ui->col(
                        $this->getColumnNameField($column, 'name')->required()
                    )->width(8)
                ),
                $this->ui->row(
                    $this->ui->col(
                        $this->getColumnPrimaryField($column, 'primary'),
                        $this->ui->span($this->ui->html('Primary'))
                            ->setStyle('margin-left:5px;')
                    )->width(3),
                    $this->ui->col(
                        $this->getColumnAutoIncrementField($column, 'autoIncrement'),
                        $this->ui->span($this->ui->html('Auto increment'))
                            ->setStyle('margin-left:5px;')
                    )->width(6),
                    $this->ui->col(
                        $this->getColumnNullableField($column, 'nullable'),
                        $this->ui->span($this->ui->html('Nullable'))
                            ->setStyle('margin-left:5px;')
                    )->width(3)
                ),
                $this->ui->row(
                    $this->ui->col(
                        $this->ui->text($this->trans->lang('Type'))
                    )->width(3),
                    $this->ui->col(
                        $this->getColumnTypeField($column, 'type')
                    )->width(6),
                    $this->ui->col(
                        $this->getColumnLengthField($column, 'length')
                    )->width(3)
                ),
                $this->ui->row(
                    $this->ui->col(
                        $this->ui->text($this->trans->lang('Unsigned'))
                    )->width(3),
                    $this->ui->col(
                        $this->getColumnUnsignedField($column, 'unsigned')
                    )->width(8)
                ),
                $this->ui->row(
                    $this->ui->col(
                        $this->ui->text($this->trans->lang('Default value'))
                    )->width(3),
                    $this->ui->col(
                        $this->getColumnDefaultField($column, 'hasDefault', 'default')
                    )->width(9)
                ),
                $this->ui->row(
                    $this->ui->col(
                        $this->ui->text($this->trans->lang('Collation'))
                    )->width(3),
                    $this->ui->col(
                        $this->getColumnCollationField($column, 'collation')
                    )->width(9)
                ),
                $this->ui->row(
                    $this->ui->col(
                        $this->ui->text($this->trans->lang('On Update'))
                    )->width(3),
                    $this->ui->col(
                        $this->getColumnOnUpdateField($column, 'onUpdate')
                    )->width(8)
                ),
                $this->ui->row(
                    $this->ui->col(
                        $this->ui->text($this->trans->lang('On Delete'))
                    )->width(3),
                    $this->ui->col(
                        $this->getColumnOnDeleteField($column, 'onDelete')
                    )->width(8)
                ),
                $this->ui->row(
                    $this->ui->col(
                        $this->ui->text($this->trans->lang('Comment'))
                    )->width(3),
                    $this->ui->col(
                        $this->getColumnCommentField($column, 'comment')
                    )->width(9)
                )
            )
        );
    }

    /**
     * @param array<ColumnEntity> $columns
     *
     * @return string
     */
    public function changes(array $columns): string
    {
        return $this->ui->build(
            $this->ui->each($columns, fn(ColumnEntity $column) =>
                $this->ui->pick([
                    $column->status === 'deleted', fn() => $this->ui->row(
                        $this->ui->col($this->ui->text($column->name))
                            ->width(3),
                        $this->ui->col($this->trans->lang('Drop'))
                            ->width(8)
                    )
                ], [
                    $column->status === 'edited', fn() => $this->ui->row(
                        $this->ui->col($this->ui->text($column->name))
                            ->width(3),
                        $this->ui->col(
                            $this->ui->div($this->trans->lang('Alter:')),
                            $this->ui->each($column->changes(), fn($change, $attr) =>
                                $this->ui->div(
                                    $this->ui->html("- $attr => {$change['to']}")
                                ))
                        )->width(8)
                    )
                ], [
                    $column->status === 'added', fn() => $this->ui->row(
                        $this->ui->col($this->ui->text($column->newName()))
                            ->width(3),
                        $this->ui->col($this->trans->lang('Add'))
                            ->width(8)
                    )
                ]
            ))
        ) ?: '&nbsp;';
    }
}
