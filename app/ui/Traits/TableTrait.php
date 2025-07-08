<?php

namespace Lagdo\DbAdmin\Ui\Traits;

use Jaxon\Script\Call\JxnClassCall;
use Lagdo\DbAdmin\Ajax\App\Db\Table\Ddl\Columns;
use Lagdo\DbAdmin\Driver\Entity\TableFieldEntity;

use function Jaxon\rq;
use function sprintf;

trait TableTrait
{
    use TableFieldTrait;

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
     * @return mixed
     */
    protected function tableHeaderNameRow(): mixed
    {
        return $this->html->formRow(
            $this->html->formCol(
                $this->html->label()->addText('Table')
            )->width(2)
        )->setClass('dbadmin-edit-table-header');
    }

    /**
     * @return mixed
     */
    protected function tableHeaderColumnRow(): mixed
    {
        return $this->html->formRow(
            $this->html->formCol(
                $this->html->label()
                    ->addText($this->trans->lang('Column'))
            )
            ->width(3)->setClass('dbadmin-table-column-left'),
            $this->html->formCol(
                $this->html->radio()
                    ->checked(true)->setName('autoIncrementCol')->setValue('0'),
                $this->html->label()->addHtml('&nbsp;AI-P')
            )
            ->width(1)->setClass('dbadmin-table-column-null-header'),
            $this->html->formCol(
                $this->html->label()
                    ->addText($this->trans->lang('Options'))
            )
            ->width(7)->setClass('dbadmin-table-column-middle'),
            $this->html->formCol(
                $this->html->when($this->support['columns'], fn() =>
                    $this->html->button()
                        ->primary()->addIcon('plus')
                        ->jxnClick(rq(Columns::class)->add())
                )
            )
            ->width(1)->setClass('dbadmin-table-column-buttons-header')
        )->setClass('dbadmin-table-column-header');
    }

    /**
     * @return mixed
     */
    protected function tableHeaderEditRow(): mixed
    {
        return $this->html->formRow(
            $this->html->formCol(
                $this->html->formInput()
                    ->setType('text')->setName('name')
                    ->setValue($table['name'] ?? '')->setPlaceholder('Name')
            )
            ->width(3)->setClass('dbadmin-edit-table-name'),
            $this->html->when(($this->engines), fn() =>
                $this->html->formCol(
                    $this->getEngineSelect($table['engine'] ?? '')
                        ->setName('engine')
                )
                ->width(2)->setClass('dbadmin-edit-table-engine')
            ),
            $this->html->when(($this->collations), fn() =>
                $this->html->formCol(
                    $this->getCollationSelect($table['collation'] ?? '')
                        ->setName('collation')
                )
                ->width(3)->setClass('dbadmin-edit-table-collation')
            ),
            $this->html->when($this->support['comment'], fn() =>
                $this->html->formCol(
                    $this->html->formInput()
                        ->setType('text')->setName('comment')
                        ->setValue($table['comment'] ?? '')
                        ->setPlaceholder($this->trans->lang('Comment'))
                )
                ->width(4)->setClass('dbadmin-table-column-middle')
            )
        )->setClass('dbadmin-edit-table-header');
    }

    /**
     * @param string $formId
     *
     * @return string
     */
    public function tableForm(string $formId): string
    {
        $tableFieldIndex = 0;
        return $this->html->build(
            $this->html->div(
                $this->html->form(
                    $this->tableHeaderNameRow(),
                    $this->tableHeaderEditRow(),
                    $this->tableHeaderColumnRow(),
                    $this->html->each($this->fields, fn($field) =>
                        $this->tableColumnElement("$formId-column", $tableFieldIndex, $field,
                            sprintf("fields[%d]", ++$tableFieldIndex))
                    )
                )
                ->responsive(true)->wrapped(false)->setId($formId)
            )
            ->jxnEvent(array_map(fn($handler) => [
                $handler['selector'],
                $handler['event'] ?? 'click',
                $handler['call']
            ], $this->handlers)),
        );
    }

    /**
     * @param string $formId
     * @param JxnClassCall $xComponent
     *
     * @return string
     */
    public function tableWrapper(string $formId, JxnClassCall $xComponent): string
    {
        return $this->html->build(
            $this->html->div(
                $this->html->form(
                    $this->tableHeaderNameRow(),
                    $this->tableHeaderEditRow(),
                    $this->tableHeaderColumnRow(),
                    $this->html->div()->jxnBind($xComponent)
                )
                ->responsive(true)->wrapped(false)->setId($formId)
            )
            ->jxnEvent(array_map(fn($handler) => [
                $handler['selector'],
                $handler['event'] ?? 'click',
                $handler['call']
            ], $this->handlers))
        );
    }

    /**
     * @param string $formId
     *
     * @return string
     */
    public function tableColumns(string $formId): string
    {
        $tableFieldIndex = 0;
        return $this->html->build(
            $this->html->each($this->fields, fn($field) =>
                $this->tableColumnElement("$formId-column", $tableFieldIndex, $field,
                    sprintf("fields[%d]", ++$tableFieldIndex))
            )
        );
    }

    /**
     * @param string $class
     * @param int $fieldIndex
     * @param TableFieldEntity $field
     * @param string $fieldPrefix
     * @param bool $wrap
     *
     * @return mixed
     */
    protected function tableColumnElement(string $class, int $fieldIndex,
        TableFieldEntity $field, string $fieldPrefix, bool $wrap = true): mixed
    {
        $this->fieldPrefix = $fieldPrefix;
        $this->fieldIndex = $fieldIndex;

        $col = $this->html->col(
            $this->html->row(
                $this->getFieldNameCol($field)
                    ->width(3)->setClass('dbadmin-table-column-left'),
                $this->getAutoIncrementCol($field)
                    ->width(1)->setClass('dbadmin-table-column-null'),
                $this->getCollectionCol($field)
                    ->width(2)->setClass('dbadmin-table-column-middle'),
                $this->getOnUpdateCol($field)
                    ->width(2)->setClass('dbadmin-table-column-middle'),
                $this->getCommentCol($field)
                    ->width(4)->setClass('dbadmin-table-column-right'),
                $this->getTypeCol($field)
                    ->width(2)->setClass('dbadmin-table-column-left second-line'),
                $this->getLengthCol($field)
                    ->width(1)->setClass('dbadmin-table-column-middle second-line'),
                $this->getNullableCol($field)
                    ->width(1)->setClass('dbadmin-table-column-null second-line'),
                $this->getUnsignedCol($field)
                    ->width(2)->setClass('dbadmin-table-column-middle second-line'),
                $this->getOnDeleteCol($field)
                    ->width(2)->setClass('dbadmin-table-column-middle second-line'),
                $this->getDefaultCol($field)
                    ->width(3)->setClass('dbadmin-table-column-middle second-line'),
                $this->getActionCol($field)
                    ->width(1)->setClass('dbadmin-table-column-buttons second-line')
            )
        )->width(12);

        return !$wrap ? $col :
            $this->html->formRow($col)
                ->setClass($class)
                ->setDataIndex($this->fieldIndex)
                ->setId(sprintf('%s-%02d', $class, $this->fieldIndex));
    }

    /**
     * @param string $class
     * @param int $index
     * @param TableFieldEntity $field
     * @param string $fieldPrefix
     * @param bool $wrap
     *
     * @return string
     */
    public function tableColumn(string $class, int $index, TableFieldEntity $field,
        string $fieldPrefix, bool $wrap): string
    {
        return $this->html->build(
            $this->tableColumnElement($class, $index, $field, $fieldPrefix, $wrap)
        );
    }

    /**
     * @param string $formId
     * @param array $fields
     *
     * @return string
     */
    public function tableQueryForm(string $formId, array $fields): string
    {
        return $this->html->build(
            $this->html->form(
                $this->html->each($fields, fn($field) =>
                    $this->html->formRow(
                        $this->html->formCol(
                            $this->html->label($field['name'])
                                ->setTitle($field['type'])
                        )
                        ->width(3),
                        $this->html->formCol(
                            $this->html->when($field['functions']['type'] === 'name', fn() =>
                                $this->html->label($field['functions']['name'])
                            ),
                            $this->html->when($field['functions']['type'] === 'select', fn() =>
                                $this->html->formSelect(
                                    $this->html->each($field['functions']['options'], fn($function) =>
                                        $this->html->option($function)
                                            ->selected($function === $field['functions']['selected'])
                                    )
                                )->setName($field['functions']['name'])
                            )
                        )
                        ->width(2),
                        $this->html->formCol(
                            $this->inputBuilder->build($field['input']['type'], $field['input'])
                        )
                        ->width(7)
                    )
                )
            )
            ->responsive(true)->wrapped(false)->setId($formId)
        );
    }
}
