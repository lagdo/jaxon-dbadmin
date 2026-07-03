<?php

namespace Lagdo\DbAdmin\App\Ui\Select;

use Jaxon\Script\Call\JxnCall;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\QueryBuilder;
use Lagdo\DbAdmin\Support\Translator;
use Lagdo\DbAdmin\App\Ui\Tab\Tab;
use Lagdo\UiBuilder\BuilderInterface;

use function count;
use function Jaxon\checked;
use function Jaxon\form;
use function Jaxon\input;
use function Jaxon\rq;

class OptionsUiBuilder
{
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
     * @param JxnCall $rqInput
     * @param string $formId
     *
     * @return mixed
     */
    private function editFormButtons(JxnCall $rqInput, string $formId): mixed
    {
        return $this->ui->row(
            $this->ui->col($this->ui->html('&nbsp;'))
                ->width(9), // Offset
            $this->ui->col(
                $this->ui->buttonGroup(
                    $this->ui->button()
                        ->primary()
                        ->addIcon('plus')
                        ->jxnClick($rqInput->add(form($formId))),
                    $this->ui->button()
                        ->danger()
                        ->addIcon('remove')
                        ->jxnClick($rqInput->del(form($formId)))
                )
            )->width(3)
        );
    }

    /**
     * @param array $values
     * @param array $options
     *
     * @return string
     */
    public function formColumns(array $values, array $options): string
    {
        $newId = 0;
        $rows = array_map(function(array $column) use($options, &$newId) {
            $row = $this->ui->row(
                $this->ui->col(
                    $this->ui->select(
                        $this->ui->option('')->selected(false),
                        $this->ui->optgroup(
                            $this->ui->each($options['functions'], fn($function) =>
                                $this->ui->option($function)
                                    ->selected($column['func'] === $function)
                            )
                        )->setLabel($this->trans->lang('Functions')),
                        $this->ui->optgroup(
                            $this->ui->each($options['grouping'], fn($grouping) =>
                                $this->ui->option($grouping)
                                    ->selected($column['func'] === $grouping)
                            )
                        )->setLabel($this->trans->lang('Aggregation')),
                    )->setName("columns[$newId][func]")
                )
                ->width(6),
                $this->ui->col(
                    $this->ui->select(
                        $this->ui->option(''),
                        $this->ui->each($options['columns'], fn($columnName) =>
                            $this->ui->option($columnName)
                                ->selected($column['column'] == $columnName)
                        )
                    )->setName("columns[$newId][column]")
                )->width(5),
                $this->ui->col(
                    $this->ui->checkbox()
                        ->checked($column['delete'] ?? false)
                        ->setName("columns[$newId][delete]")
                )->width(1)
            );
            $newId++;

            return $row;
        }, $values['columns'] ?? []);

        return $this->ui->build($this->ui->form(...$rows));
    }

    /**
     * @return string
     */
    public function columnFormId(): string
    {
        return $this->tab()->app()->id('dbadmin-table-select-columns-form');
    }

    /**
     * @return string
     */
    public function editColumns(): string
    {
        $rqColumns = rq(QueryBuilder\Fields\Form\Columns::class);
        return $this->ui->build(
            $this->ui->form(
                $this->editFormButtons($rqColumns, $this->columnFormId()),
                $this->ui->div()->tbnBindApp($rqColumns)
            )->setId($this->columnFormId())
        );
    }

    /**
     * @param array $values
     * @param array $options
     *
     * @return string
     */
    public function formFilters(array $values, array $options): string
    {
        $newId = 0;
        $rows = array_map(function(array $filter) use($options, &$newId) {
            $row = $this->ui->row(
                $this->ui->col(
                    $this->ui->select(
                        $this->ui->option('(' . $this->trans->lang('anywhere') . ')')
                            ->setValue(''),
                        $this->ui->each($options['columns'], fn($columnName) =>
                            $this->ui->option($columnName)
                                ->selected($filter['column'] === $columnName)
                        )
                    )->setName("filters[$newId][column]")
                )->width(4),
                $this->ui->col(
                    $this->ui->select(
                        $this->ui->each($options['operators'], fn($operator) =>
                            $this->ui->option($operator)
                                ->selected($filter['operator'] === $operator)
                        )
                    )->setName("filters[$newId][operator]")
                )->width(3),
                $this->ui->col(
                    $this->ui->input()
                        ->setName("filters[$newId][operand]")
                        ->setValue($filter['operand'])
                )->width(4),
                $this->ui->col(
                    $this->ui->checkbox()
                        ->checked($filter['delete'] ?? false)
                        ->setName("filters[$newId][delete]")
                )->width(1)
            );
            $newId++;

            return $row;
        }, $values['filters'] ?? []);

        return $this->ui->build($this->ui->form(...$rows));
    }

    /**
     * @return string
     */
    public function filterFormId(): string
    {
        return $this->tab()->app()->id('dbadmin-table-select-filters-form');
    }

    /**
     * @return string
     */
    public function editFilters(): string
    {
        $rqFilters = rq(QueryBuilder\Fields\Form\Filters::class);
        return $this->ui->build(
            $this->ui->form(
                $this->editFormButtons($rqFilters, $this->filterFormId()),
                $this->ui->div()
                    ->tbnBindApp($rqFilters)
            )->setId($this->filterFormId())
        );
    }

    /**
     * @param array $values
     * @param array $options
     *
     * @return string
     */
    public function formSorters(array $values, array $options): string
    {
        $newId = 0;
        $newId = 0;
        $rows = array_map(function(array $sorter) use($options, &$newId) {
            $row = $this->ui->row(
                $this->ui->col(
                    $this->ui->select(
                        $this->ui->each($options['columns'], fn($columnName) =>
                            $this->ui->option($columnName)
                                ->selected($sorter['column'] === $columnName)
                        )
                    )->setName("sorters[$newId][column]")
                )->width(6),
                $this->ui->col(
                    $this->ui->inputGroup(
                        $this->ui->label(
                            $this->ui->text($this->trans->lang('descending'))
                        ),
                        $this->ui->checkbox()
                            ->checked($sorter['desc'] ?? false)
                            ->setName("sorters[$newId][desc]")
                            ->setValue('1')
                    )
                )->width(5),
                $this->ui->col(
                    $this->ui->checkbox()
                        ->checked($sorter['delete'] ?? false)
                        ->setName("sorters[$newId][delete]")
                )->width(1)
            );
            $newId++;

            return $row;
        }, $values['sorters'] ?? []);

        return $this->ui->build($this->ui->form(...$rows));
    }

    /**
     * @return string
     */
    public function sorterFormId(): string
    {
        return $this->tab()->app()->id('dbadmin-table-select-sorters-form');
    }

    /**
     * @return string
     */
    public function editSorters(): string
    {
        $rqSorters = rq(QueryBuilder\Fields\Form\Sorters::class);
        return $this->ui->build(
            $this->ui->form(
                $this->editFormButtons($rqSorters, $this->sorterFormId()),
                $this->ui->div()
                    ->tbnBindApp($rqSorters)
            )->setId($this->sorterFormId())
        );
    }

    /**
     * @param array $options
     *
     * @return string
     */
    public function optionsFields(array $options): string
    {
        $columnCount = count($options['columns'] ?? []);
        $filterCount = count($options['filters'] ?? []);
        $sorterCount = count($options['sorters'] ?? []);

        return $this->ui->build(
            $this->ui->buttonGroup(
                $this->ui->button(
                    $this->ui->text($this->trans->lang('Columns ')),
                    $this->ui->when($columnCount > 0, fn() =>
                        $this->ui->badge((string)$columnCount)->primary())
                )->outline()
                    ->secondary()
                    ->fullWidth()
                    ->jxnClick(rq(QueryBuilder\Fields\Columns::class)->edit()),
                $this->ui->button(
                    $this->ui->text($this->trans->lang('Filters ')),
                    $this->ui->when($filterCount > 0, fn() =>
                        $this->ui->badge((string)$filterCount)->primary())
                )->outline()
                    ->secondary()
                    ->fullWidth()
                    ->jxnClick(rq(QueryBuilder\Fields\Filters::class)->edit()),
                $this->ui->button(
                    $this->ui->text($this->trans->lang('Order ')),
                    $this->ui->when($sorterCount > 0, fn() =>
                        $this->ui->badge((string)$sorterCount)->primary())
                )->outline()
                    ->secondary()
                    ->fullWidth()
                    ->jxnClick(rq(QueryBuilder\Fields\Sorters::class)->edit())
            )->fullWidth()
        );
    }

    /**
     * @param array $options
     *
     * @return string
     */
    public function optionsValues(array $options): string
    {
        $optionsLimitId = $this->tab()->app()->id('dbadmin-table-select-options-form-limit');
        $optionsTotalId = $this->tab()->app()->id('dbadmin-table-select-options-form-total');
        $optionsLengthId = $this->tab()->app()->id('dbadmin-table-select-options-form-length');
        $rqOptionsValues = rq(QueryBuilder\Values::class);
        $selectLimitValue = input($optionsLimitId)->toInt();
        $selectTotalValue = checked($optionsTotalId);
        $textLengthValue = input($optionsLengthId)->toInt();

        return $this->ui->build(
            $this->ui->form(
                $this->ui->div(
                    $this->ui->div(
                        $this->ui->inputGroup(
                            $this->ui->label(
                                $this->ui->text($this->trans->lang('Limit'))
                            ),
                            $this->ui->input()
                                ->setId($optionsLimitId)
                                // ->setType('number')
                                ->setName('limit')
                                ->setValue($options['limit']),
                            $this->ui->button()
                                ->outline()
                                ->secondary()
                                ->addIcon('ok')
                                ->jxnClick($rqOptionsValues->saveSelectLimit($selectLimitValue))
                        )
                    )->setStyle('width:30%;'),
                    $this->ui->div(
                        $this->ui->inputGroup(
                            $this->ui->label(
                                $this->ui->text($this->trans->lang('Total'))
                            ),
                            $this->ui->checkbox()
                                ->setId($optionsTotalId)
                                ->setName('total')
                                ->checked($options['total'])
                                ->setValue('1'),
                            $this->ui->button()
                                ->outline()
                                ->secondary()
                                ->addIcon('ok')
                                ->jxnClick($rqOptionsValues->saveSelectTotal($selectTotalValue))
                        )
                    )->setStyle('width:auto; margin:auto;'),
                    $this->ui->div(
                        $this->ui->inputGroup(
                            $this->ui->label(
                                $this->ui->text($this->trans->lang('Text length'))
                            ),
                            $this->ui->input()
                                ->setId($optionsLengthId)
                                // ->setType('number')
                                ->setName('length')
                                ->setValue($options['length']),
                            $this->ui->button()
                                ->outline()
                                ->secondary()
                                ->addIcon('ok')
                                ->jxnClick($rqOptionsValues->saveTextLength($textLengthValue))
                        )
                    )->setStyle('width:40%;')
                )->setStyle('display:flex; flex-direction:row; align-items:flex-start;')
            )
        );
    }
}
