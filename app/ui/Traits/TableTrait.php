<?php

namespace Lagdo\DbAdmin\Ui\Traits;

use Jaxon\Script\Call\JxnClassCall;
use Lagdo\DbAdmin\Ajax\App\Db\Table\Ddl\Columns;
use Lagdo\DbAdmin\Driver\Entity\TableFieldEntity;
use Lagdo\UiBuilder\BuilderInterface;

use function Jaxon\rq;
use function sprintf;

trait TableTrait
{
    use TableFieldTrait;

    /**
     * @return BuilderInterface
     */
    abstract protected function builder(): BuilderInterface;

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
     * @param array $support
     *
     * @return self
     */
    public function support(array $support): self
    {
        $this->support = $support;
        return $this;
    }

    /**
     * @param array $engines
     *
     * @return self
     */
    public function engines(array $engines): self
    {
        $this->engines = $engines;
        return $this;
    }

    /**
     * @param array $collations
     *
     * @return self
     */
    public function collations(array $collations): self
    {
        $this->collations = $collations;
        return $this;
    }

    /**
     * @param array $unsigned
     *
     * @return self
     */
    public function unsigned(array $unsigned): self
    {
        $this->unsigned = $unsigned;
        return $this;
    }

    /**
     * @param array $foreignKeys
     *
     * @return self
     */
    public function foreignKeys(array $foreignKeys): self
    {
        $this->foreignKeys = $foreignKeys;
        return $this;
    }

    /**
     * @param array $options
     *
     * @return self
     */
    public function options(array $options): self
    {
        $this->options = $options;
        return $this;
    }

    /**
     * @param array $fields
     *
     * @return self
     */
    public function fields(array $fields): self
    {
        $this->fields = $fields;
        return $this;
    }

    /**
     * @param array $handlers
     *
     * @return self
     */
    public function handlers(array $handlers): self
    {
        $this->handlers = $handlers;
        return $this;
    }

    /**
     * @param string $formId
     *
     * @return self
     */
    public function formId(string $formId): self
    {
        $this->formId = $formId;
        return $this;
    }

    /**
     * @return mixed
     */
    protected function tableHeaderNameRow(): mixed
    {
        $html = $this->builder();
        return $html->formRow(
            $html->formCol(
                $html->label()->addText('Table')
            )->width(2)
        )->setClass('dbadmin-edit-table-header');
    }

    /**
     * @return mixed
     */
    protected function tableHeaderEditRow(): mixed
    {
        $hasEngines = count($this->engines) > 0;
        $hasCollations = count($this->collations) > 0;
        $html = $this->builder();
        return $html->list(
            $html->formRow(
                $html->formCol(
                    $html->formInput()
                        ->setType('text')->setName('name')
                        ->setValue($this->table['name'] ?? '')->setPlaceholder('Name')
                )
                ->width(4)->setClass('dbadmin-edit-table-name'),
                $html->formCol(
                    $html->label()->addHtml('&nbsp')
                )
                ->width(1)->setClass('dbadmin-table-column-middle'),
                $html->when($hasEngines, fn() =>
                    $html->formCol(
                        $this->getEngineSelect($this->table['engine'] ?? '')
                            ->setName('engine')
                    )
                    ->width(3)->setClass('dbadmin-edit-table-engine')
                ),
                $html->when($hasCollations, fn() =>
                    $html->formCol(
                        $this->getCollationSelect($this->table['collation'] ?? '')
                            ->setName('collation')
                    )
                    ->width(4)->setClass('dbadmin-edit-table-collation')
                ),
                $html->when($hasEngines || $hasCollations, fn() =>
                    $html->formCol(
                        $html->label()->addHtml('&nbsp')
                    )
                    ->width(5)->setClass('dbadmin-table-column-middle')
                ),
                $html->when(isset($this->support['comment']), fn() =>
                    $html->formCol(
                        $html->formInput()
                            ->setType('text')->setName('comment')
                            ->setValue($this->table['comment'] ?? '')
                            ->setPlaceholder($this->trans->lang('Comment'))
                    )
                    ->width(6)->setClass('dbadmin-table-column-right')
                )
            )->setClass('dbadmin-edit-table-header'),
        );
    }

    /**
     * @return mixed
     */
    protected function tableHeaderColumnRow(): mixed
    {
        $html = $this->builder();
        return $html->formRow(
            $html->formCol(
                $html->label()
                    ->addText($this->trans->lang('Column'))
            )
            ->width(4)->setClass('dbadmin-table-column-left'),
            $html->formCol(
                $html->radio()
                    ->checked(true)->setName('autoIncrementCol')
                    ->setValue('0')->setStyle('margin-top: 5px'),
                $html->label()->addHtml('&nbsp;AI-P')->setStyle('padding-top: 3px')
            )
            ->width(1)->setClass('dbadmin-table-column-middle'),
            $html->formCol(
                $html->label()
                    ->addText($this->trans->lang('Options'))
            )
            ->width(6)->setClass('dbadmin-table-column-middle'),
            $html->formCol(
                $html->when($this->support['columns'], fn() =>
                    $html->button()
                        ->primary()->addIcon('plus')
                        ->jxnClick(rq(Columns::class)->add($this->formValues()))
                )
            )
            ->width(1)->setClass('dbadmin-table-column-buttons-header')
        )->setClass('dbadmin-table-column-header');
    }

    /**
     * @return string
     */
    public function tableForm(): string
    {
        $html = $this->builder();
        return $html->build(
            $html->div(
                $html->form(
                    $this->tableHeaderNameRow(),
                    $this->tableHeaderEditRow(),
                    $this->tableHeaderColumnRow(),
                    $html->each($this->fields, fn($field) =>
                        $this->tableColumnElement("{$this->formId}-column", $field,
                            sprintf("fields[%d]", $field->editPosition))
                    )
                )
                ->responsive(true)->wrapped(false)->setId($this->formId)
            )
            ->jxnEvent(array_map(fn($handler) => [
                $handler['selector'],
                $handler['event'] ?? 'click',
                $handler['call']
            ], $this->handlers)),
        );
    }

    /**
     * @param JxnClassCall $xComponent
     *
     * @return string
     */
    public function tableWrapper(JxnClassCall $xComponent): string
    {
        $html = $this->builder();
        return $html->build(
            $html->div(
                $html->form(
                    $this->tableHeaderNameRow(),
                    $this->tableHeaderEditRow(),
                    $this->tableHeaderColumnRow(),
                    $html->div()->jxnBind($xComponent)
                )
                ->responsive(true)->wrapped(false)->setId($this->formId)
            )
            ->jxnEvent(array_map(fn($handler) => [
                $handler['selector'],
                $handler['event'] ?? 'click',
                $handler['call']
            ], $this->handlers))
        );
    }

    /**
     * @return string
     */
    public function tableColumns(): string
    {
        $html = $this->builder();
        return $html->build(
            $html->each($this->fields, fn($field) =>
                $this->tableColumnElement("{$this->formId}-column", $field,
                    sprintf("fields[%d]", $field->editPosition))
            )
        );
    }

    /**
     * @param string $class
     * @param TableFieldEntity $field
     * @param string $editPrefix
     * @param bool $wrap
     *
     * @return mixed
     */
    protected function tableColumnElement(string $class, TableFieldEntity $field,
        string $editPrefix, bool $wrap = true): mixed
    {
        $this->editPrefix = $editPrefix;
        $html = $this->builder();

        $col = $html->col(
            $html->row(
                $this->getFieldNameCol($field)
                    ->width(4)->setClass('dbadmin-table-column-left'),
                $this->getAutoIncrementCol($field)
                    ->width(1)->setClass('dbadmin-table-column-middle')
                    ->setStyle('padding-top: 7px'),
                $this->getOnUpdateCol($field)
                    ->width(3)->setClass('dbadmin-table-column-middle'),
                $this->getCollationCol($field)
                    ->width(4)->setClass('dbadmin-table-column-right'),
                $this->getTypeCol($field)
                    ->width(3)->setClass('dbadmin-table-column-left second-line'),
                $this->getLengthCol($field)
                    ->width(1)->setClass('dbadmin-table-column-middle second-line'),
                $this->getNullableCol($field)
                    ->width(1)->setClass('dbadmin-table-column-middle second-line')
                    ->setStyle('padding-top: 7px'),
                $this->getOnDeleteCol($field)
                    ->width(3)->setClass('dbadmin-table-column-middle second-line'),
                $this->getUnsignedCol($field)
                    ->width(4)->setClass('dbadmin-table-column-right second-line'),
                $this->getDefaultCol($field)
                    ->width(5)->setClass('dbadmin-table-column-left second-line'),
                $this->getCommentCol($field)
                    ->width(6)->setClass('dbadmin-table-column-middle second-line'),
                $this->getActionCol($field)
                    ->width(1)->setClass('dbadmin-table-column-buttons second-line')
            )
        )->width(12);

        return !$wrap ? $col :
            $html->formRow($col)
                ->setClass($class);
    }

    /**
     * @param array $fields
     *
     * @return string
     */
    public function tableQueryForm(array $fields): string
    {
        $html = $this->builder();
        return $html->build(
            $html->form(
                $html->each($fields, fn($field) =>
                    $html->formRow(
                        $html->formCol(
                            $html->label($field['name'])
                                ->setTitle($field['type'])
                        )
                        ->width(3),
                        $html->formCol(
                            $html->when($field['functions']['type'] === 'name', fn() =>
                                $html->label($field['functions']['name'])
                            ),
                            $html->when($field['functions']['type'] === 'select', fn() =>
                                $html->formSelect(
                                    $html->each($field['functions']['options'], fn($function) =>
                                        $html->option($function)
                                            ->selected($function === $field['functions']['selected'])
                                    )
                                )->setName($field['functions']['name'])
                            )
                        )
                        ->width(2),
                        $html->formCol(
                            $this->inputBuilder->build($field['input']['type'], $field['input'])
                        )
                        ->width(7)
                    )
                )
            )
            ->responsive(true)->wrapped(false)->setId($this->formId)
        );
    }
}
