<?php

namespace Lagdo\DbAdmin\Ui\Table;

use Jaxon\Script\Call\JxnClassCall;
use Lagdo\DbAdmin\Ajax\Admin\Db\Table\Ddl\Columns;
use Lagdo\DbAdmin\Driver\Entity\TableFieldEntity;
use Lagdo\DbAdmin\Db\Translator;
use Lagdo\DbAdmin\Ui\InputBuilder;
use Lagdo\DbAdmin\Ui\PageTrait;
use Lagdo\UiBuilder\BuilderInterface;

use function Jaxon\rq;
use function sprintf;

class TableUiBuilder
{
    use PageTrait;
    use TableTrait;
    use TableFieldTrait;

    /**
     * @param Translator $trans
     * @param BuilderInterface $ui
     * @param InputBuilder $inputBuilder
     */
    public function __construct(protected Translator $trans,
        protected BuilderInterface $ui, protected InputBuilder $inputBuilder)
    {}

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
    protected function headerNameRow(): mixed
    {
        return $this->ui->formRow(
            $this->ui->formCol(
                $this->ui->label($this->ui->text('Table'))
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
            )
            ->width(4)
            ->setClass('dbadmin-table-column-left'),
            $this->ui->formCol(
                $this->ui->radio()
                    ->checked(true)
                    ->setName('autoIncrementCol')
                    ->setValue('0')
                    ->setStyle('margin-top: 5px'),
                $this->ui->label($this->ui->html('&nbsp;AI-P'))
                    ->setStyle('padding-top: 3px')
            )->width(1)
                ->setClass('dbadmin-table-column-middle'),
            $this->ui->formCol(
                $this->ui->label($this->ui->text($this->trans->lang('Options')))
            )->width(6)
                ->setClass('dbadmin-table-column-middle'),
            $this->ui->formCol(
                $this->ui->when($this->support['columns'], fn() =>
                    $this->ui->button()
                        ->primary()->addIcon('plus')
                        ->jxnClick(rq(Columns::class)->add($this->formValues()))
                )
            )->width(1)
                ->setClass('dbadmin-table-column-buttons-header')
        )->setClass('dbadmin-table-column-header');
    }

    /**
     * @param JxnClassCall $xComponent
     *
     * @return string
     */
    public function wrapper(JxnClassCall $xComponent): string
    {
        return $this->ui->build(
            $this->ui->div(
                $this->ui->form(
                    $this->headerNameRow(),
                    $this->headerEditRow(),
                    $this->headerColumnRow(),
                    $this->ui->div()->jxnBind($xComponent)
                )->responsive(true)->wrapped(false)->setId($this->formId)
            )
            ->jxnEvent(array_map(fn($handler) => [
                $handler['selector'],
                $handler['event'] ?? 'click',
                $handler['call']
            ], $this->handlers))
        );
    }

    /**
     * @param TableFieldEntity $field
     *
     * @return mixed
     */
    private function getColumnBgColor(TableFieldEntity $field): string
    {
        $style = 'background-color: ';
        return match($field->editStatus) {
            'added' => "$style #e6ffe6;",
            'deleted' => "$style #ffe6e6;",
            default => "$style white;",
        };
    }

    /**
     * @param TableFieldEntity $field
     *
     * @return mixed
     */
    protected function columnElement(TableFieldEntity $field): mixed
    {
        $this->editPrefix = sprintf("fields[%d]", $field->editPosition);

        return $this->ui->formRow(
            $this->getFieldNameCol($field)
                ->width(4)
                ->setClass('dbadmin-table-column-left'),
            $this->getAutoIncrementCol($field)
                ->width(1)
                ->setClass('dbadmin-table-column-middle')
                ->setStyle('padding-top: 7px'),
            $this->ui->formCol(
                $this->ui->formRow(
                    $this->getCommentCol($field)
                        ->width(11)
                        ->setClass('dbadmin-table-column-middle nested-col'),
                    $this->getActionCol($field)
                        ->width(1)
                        ->setClass('dbadmin-table-column-buttons nested-col')
                )->setClass('nested-row')
            )->width(7),

            $this->ui->formCol(
                $this->ui->formRow(
                    $this->getTypeCol($field)
                        ->width(8)
                        ->setClass('dbadmin-table-column-left nested-col'),
                    $this->getLengthCol($field)
                        ->width(4)
                        ->setClass('dbadmin-table-column-right nested-col'),
                )->setClass('nested-row')
            )->width(4)
                ->setClass('second-line'),
            $this->getNullableCol($field)
                ->width(1)
                ->setClass('dbadmin-table-column-middle second-line')
                ->setStyle('padding-top: 7px'),
            $this->getCollationCol($field)
                ->width(4)
                ->setClass('dbadmin-table-column-middle second-line'),
            $this->getOnUpdateCol($field)
                ->width(3)
                ->setClass('dbadmin-table-column-right second-line'),

            $this->getDefaultCol($field)
                ->width(5)
                ->setClass('dbadmin-table-column-left second-line'),
            $this->getUnsignedCol($field)
                ->width(4)
                ->setClass('dbadmin-table-column-middle second-line'),
            $this->getOnDeleteCol($field)
                ->width(3)
                ->setClass('dbadmin-table-column-right second-line'),
        )->setClass("{$this->formId}-column");
    }

    /**
     * @return string
     */
    public function columns(): string
    {
        return $this->ui->build(
            $this->ui->each($this->fields, fn($field) =>
                $this->ui->div(
                    $this->columnElement($field)
                )->setClass('dbadmin-table-edit-field')
                    ->setStyle($this->getColumnBgColor($field))
            )
        );
    }

    /**
     * @param array $fields
     * @param string $maxHeight
     *
     * @return string
     */
    public function queryForm(array $fields, string $maxHeight = ''): string
    {
        $form = $this->ui->form(
            $this->ui->each($fields, fn($field) =>
                $this->ui->formRow(
                    $this->ui->formCol(
                        $this->ui->label($field['name'])
                            ->setTitle($field['type'])
                    )->width(3),
                    $this->ui->formCol(
                        $this->ui->when($field['functions']['type'] === 'name', fn() =>
                            $this->ui->label($field['functions']['name'])
                        ),
                        $this->ui->when($field['functions']['type'] === 'select', fn() =>
                            $this->ui->formSelect(
                                $this->ui->each($field['functions']['options'], fn($function) =>
                                    $this->ui->option($function)
                                        ->selected($function === $field['functions']['selected'])
                                )
                            )->setName($field['functions']['name'])
                        )
                    )->width(2),
                    $this->ui->formCol(
                        $this->inputBuilder->build($field['input']['type'], $field['input'])
                    )->width(7)
                )
            )
        )->responsive(true)->wrapped(false)->setId($this->formId);

        return $maxHeight === '' ? $this->ui->build($form) : $this->ui->build(
            $this->ui->div($form)
                ->setStyle("max-height:$maxHeight; overflow-x:hidden; overflow-y:scroll;")
        );
    }
}
