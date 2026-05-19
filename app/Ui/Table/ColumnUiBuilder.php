<?php

namespace Lagdo\DbAdmin\App\Ui\Table;

use Lagdo\DbAdmin\App\Ui\Tab\Tab;
use Lagdo\DbAdmin\App\Ui\Table\Column\ColumnTrait;
use Lagdo\DbAdmin\Driver\Sql\Dto\TableAlterDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\TableCreateDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\TableDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\Ddl\ColumnFormDto;
use Lagdo\DbAdmin\Support\Translator;
use Lagdo\UiBuilder\BuilderInterface;

use function count;

class ColumnUiBuilder
{
    use ColumnTrait;

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
     * @param ColumnFormDto $input
     * @param string $primaryColumn
     *
     * @return string
     */
    public function column(ColumnFormDto $input, string $primaryColumn): string
    {
        $canBePrimary = true; // $input->values()->name === $primaryColumn;
        $this->listMode = false;
        $support = $this->support(['comment']);
        $editableProps = ['unsigned', 'collation', 'onUpdate', 'onDelete'];

        return $this->ui->build(
            $this->ui->form(
                // Hidden inputs for non editable properties.
                $this->ui->each($editableProps, function(string $prop) use($input) {
                    $attr = "{$prop}Editable";
                    return $this->ui->when(!$input->$attr, fn() =>
                        $this->ui->input(['name' => $prop])->setType('hidden')
                    );
                }),
                $this->ui->row(
                    $this->ui->col(
                        $this->ui->text($this->trans->lang('Name'))
                    )->width(3),
                    $this->ui->col(
                        $this->getColumnNameField($input, 'name')->required()
                    )->width(8)
                ),
                $this->ui->row(
                    $this->ui->col(
                        // Show the checkbox only if this column is or can be the primary key.
                        $this->ui->when(!$canBePrimary, fn() => ''),
                        $this->ui->when($canBePrimary, fn() =>
                            $this->ui->list(
                                $this->getColumnPrimaryField($input, 'primary'),
                                $this->ui->span($this->ui->html('Primary'))
                                    ->setStyle('margin-left:5px;')
                            )
                        )
                    )->width(3),
                    $this->ui->col(
                        $this->getColumnAutoIncrementField($input, 'autoIncrement'),
                        $this->ui->span($this->ui->html('Auto increment'))
                            ->setStyle('margin-left:5px;')
                    )->width(6),
                    $this->ui->col(
                        $this->getColumnNullableField($input, 'nullable'),
                        $this->ui->span($this->ui->html('Nullable'))
                            ->setStyle('margin-left:5px;')
                    )->width(3)
                ),
                $this->ui->row(
                    $this->ui->col(
                        $this->ui->text($this->trans->lang('Type'))
                    )->width(3),
                    $this->ui->col(
                        $this->getColumnTypeField($input, 'type')
                    )->width(6),
                    $this->ui->col(
                        $this->getColumnLengthField($input, 'length')
                            ->when($input->lengthRequired, fn($input) => $input->setRequired('required'))
                    )->width(3)
                ),
                $this->ui->when($input->unsignedEditable, fn() =>
                    $this->ui->row(
                        $this->ui->col(
                            $this->ui->text($this->trans->lang('Unsigned'))
                        )->width(3),
                        $this->ui->col(
                            $this->getColumnUnsignedField($input, 'unsigned')
                        )->width(8)
                    )
                ),
                $this->ui->row(
                    $this->ui->col(
                        $this->ui->text($this->trans->lang('Default value'))
                    )->width(3),
                    $this->ui->col(
                        $this->getColumnDefaultField($input, 'generated', 'default')
                    )->width(9)
                ),
                $this->ui->when($input->collationEditable, fn() =>
                    $this->ui->row(
                        $this->ui->col(
                            $this->ui->text($this->trans->lang('Collation'))
                        )->width(3),
                        $this->ui->col(
                            $this->getColumnCollationField($input, 'collation')
                        )->width(9)
                    )
                ),
                $this->ui->when($input->onUpdateEditable, fn() =>
                    $this->ui->row(
                        $this->ui->col(
                            $this->ui->text($this->trans->lang('On Update'))
                        )->width(3),
                        $this->ui->col(
                            $this->getColumnOnUpdateField($input, 'onUpdate')
                        )->width(8)
                    )
                ),
                $this->ui->when($input->onDeleteEditable, fn() =>
                    $this->ui->row(
                        $this->ui->col(
                            $this->ui->text($this->trans->lang('On Delete'))
                        )->width(3),
                        $this->ui->col(
                            $this->getColumnOnDeleteField($input, 'onDelete')
                        )->width(8)
                    )
                ),
                $this->ui->when($support['comment'], fn() =>
                    $this->ui->row(
                        $this->ui->col(
                            $this->ui->text($this->trans->lang('Comment'))
                        )->width(3),
                        $this->ui->col(
                            $this->getColumnCommentField($input, 'comment', 'setComment')
                        )->width(9)
                    )
                )
            )->setId($this->editFormId())
        );
    }

    /**
     * @param array<ColumnFormDto> $columns
     *
     * @return mixed
     */
    private function tableColumns(array $columns): mixed
    {
        return $this->ui->each($columns, fn(ColumnFormDto $input) =>
            $this->ui->pick([
                $input->dropped(), fn() => $this->ui->row(
                    $this->ui->col($this->ui->text($input->column->name . ':'))
                        ->width(3),
                    $this->ui->col($this->trans->lang('Drop'))
                        ->width(8)
                )
            ], [
                $input->changed(), fn() => $this->ui->row(
                    $this->ui->col($this->ui->text($input->column->name . ':'))
                        ->width(3),
                    $this->ui->col(
                        $this->ui->div($this->trans->lang('Alter:')),
                        $this->ui->each($input->changes(), fn($change, $attr) =>
                            $this->ui->div(
                                $this->ui->html("- $attr => {$change['to']}")
                            ))
                    )->width(8)
                )
            ], [
                $input->added(), fn() => $this->ui->row(
                    $this->ui->col($this->ui->text($input->newName() . ':'))
                        ->width(3),
                    $this->ui->col($this->trans->lang('Add'))
                        ->width(8)
                )
            ]
        ));
    }

    /**
     * @param TableCreateDto $createDto
     * @param array<ColumnFormDto> $columns
     *
     * @return string
     */
    public function createValues(TableCreateDto $createDto, array $columns): string
    {
        $hasEngines = count($this->engines()) > 0;
        $hasCollations = count($this->collations()) > 0;
        $support = $this->support(['comment']);

        return $this->ui->build(
            $this->ui->div('<b>' . $this->trans->lang('Table') . '</b>'),
            $this->ui->row(
                $this->ui->col($this->trans->lang('Name:'))
                    ->width(3),
                $this->ui->col($this->ui->text($createDto->name))
                    ->width(8)
            ),
            $this->ui->when($createDto->hasAutoIncrement, fn() =>
                $this->ui->row(
                    $this->ui->col($this->trans->lang('Auto increment:'))
                        ->width(3),
                    $this->ui->col($this->ui->text($createDto->autoIncrement))
                        ->width(8)
                ),
            ),
            $this->ui->when($hasEngines && $createDto->engine !== '', fn() =>
                $this->ui->row(
                    $this->ui->col($this->trans->lang('Engine:'))
                        ->width(3),
                    $this->ui->col($this->ui->text($createDto->engine))
                        ->width(8)
                ),
            ),
            $this->ui->when($hasCollations && $createDto->collation !== '', fn() =>
                $this->ui->row(
                    $this->ui->col($this->trans->lang('Collation:'))
                        ->width(3),
                    $this->ui->col($this->ui->text($createDto->collation))
                        ->width(8)
                ),
            ),
            $this->ui->when($support['comment'] && $createDto->setComment, fn() =>
                $this->ui->row(
                    $this->ui->col($this->trans->lang('Comment:'))
                        ->width(3),
                    $this->ui->col($createDto->comment)
                        ->width(8)
                ),
            ),
            $this->ui->div('<b>' . $this->trans->lang('Columns') . '</b>'),
            $this->tableColumns($columns)
        );
    }

    /**
     * @param TableDto $tableDto
     * @param TableAlterDto $alterDto
     * @param array<ColumnFormDto> $columns
     *
     * @return string
     */
    public function alterValues(TableDto $tableDto, TableAlterDto $alterDto, array $columns): string
    {
        $hasEngines = count($this->engines()) > 0;
        $hasCollations = count($this->collations()) > 0;
        $support = $this->support(['comment']);

        return $this->ui->build(
            $this->ui->div('<b>' . $this->trans->lang('Table') . '</b>'),
            $this->ui->when($alterDto->name !== $tableDto->name, fn() =>
                $this->ui->row(
                    $this->ui->col($this->trans->lang('Name:'))
                        ->width(3),
                    $this->ui->col($this->ui->text($alterDto->name))
                        ->width(8)
                )
            ),
            $this->ui->when($alterDto->hasAutoIncrement, fn() =>
                $this->ui->row(
                    $this->ui->col($this->trans->lang('Auto increment:'))
                        ->width(3),
                    $this->ui->col($this->ui->text($alterDto->autoIncrement))
                        ->width(8)
                ),
            ),
            $this->ui->when($hasEngines && $alterDto->engine !== $tableDto->engine, fn() =>
                $this->ui->row(
                    $this->ui->col($this->trans->lang('Engine:'))
                        ->width(3),
                    $this->ui->col($this->ui->text($alterDto->engine))
                        ->width(8)
                ),
            ),
            $this->ui->when($hasCollations && $alterDto->collation !== $tableDto->collation, fn() =>
                $this->ui->row(
                    $this->ui->col($this->trans->lang('Collation:'))
                        ->width(3),
                    $this->ui->col($this->ui->text($alterDto->collation))
                        ->width(8)
                ),
            ),
            $this->ui->when($support['comment'] && $alterDto->setComment, fn() =>
                $this->ui->row(
                    $this->ui->col($this->trans->lang('Comment:'))
                        ->width(3),
                    $this->ui->col($alterDto->comment)
                        ->width(8)
                ),
            ),
            $this->ui->div('<b>' . $this->trans->lang('Columns') . '</b>'),
            $this->tableColumns($columns)
        );
    }

    /**
     * @return string
     */
    public function getQueryDivId(): string
    {
        return $this->tab()->app()->id('dbadmin-table-show-sql-query');
    }

    /**
     * @param string $queryText
     *
     * @return string
     */
    public function sqlCodeElement(string $queryText): string
    {
        return $this->ui->build(
            $this->ui->row(
                $this->ui->col(
                    $this->ui->panel(
                        $this->ui->panelBody(
                            $this->ui->div($queryText)
                                ->setId($this->getQueryDivId())
                                ->setStyle('height: 300px;')
                        )->setStyle('padding: 0 1px;')
                    )->setStyle('padding: 5px;')
                        ->look('default')
                )->width(12)
            )
        );
    }
}
