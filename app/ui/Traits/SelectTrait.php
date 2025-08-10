<?php

namespace Lagdo\DbAdmin\Ui\Traits;

use Jaxon\Script\Call\JxnCall;
use Lagdo\DbAdmin\Ajax\App\Db\Table\Dql\Input\Columns;
use Lagdo\DbAdmin\Ajax\App\Db\Table\Dql\Input\Filters;
use Lagdo\DbAdmin\Ajax\App\Db\Table\Dql\Input\Sorting;
use Lagdo\DbAdmin\Ajax\App\Db\Table\Dql\Options;
use Lagdo\DbAdmin\Ajax\App\Db\Table\Dql\QueryText;
use Lagdo\DbAdmin\Ajax\App\Db\Table\Dql\Results;
use Lagdo\UiBuilder\BuilderInterface;

use function array_shift;
use function count;
use function Jaxon\rq;
use function Jaxon\pm;

trait SelectTrait
{
    /**
     * @return BuilderInterface
     */
    abstract protected function builder(): BuilderInterface;

    /**
     * @param JxnCall $rqInput
     * @param string $formId
     *
     * @return mixed
     */
    private function editFormButtons(JxnCall $rqInput, string $formId): mixed
    {
        $html = $this->builder();
        return $html->formRow(
            $html->formCol($html->html('&nbsp;'))
                ->width(9), // Offset
            $html->formCol(
                $html->buttonGroup(
                    $html->button()->primary()
                        ->addIcon('plus')
                        ->jxnClick($rqInput->add(pm()->form($formId))),
                    $html->button()->danger()
                        ->addIcon('remove')
                        ->jxnClick($rqInput->del(pm()->form($formId)))
                )
                ->fullWidth(false)
            )
            ->width(3)
        );
    }

    /**
     * @param array $values
     * @param array $options
     *
     * @return string
     */
    public function formQueryColumns(array $values, array $options): string
    {
        $columns = $values['column'] ?? [];
        $count = count($columns);
        $formRows = [];
        $html = $this->builder();

        for ($curId = 0, $newId = 0; $curId < $count; $curId++) {
            // Do not render deleted items
            if (isset($values['del'][$curId])) {
                continue;
            }

            $formRows[] = $html->formRow(
                $html->formCol(
                    $html->formSelect(
                        $html->option('')->selected(false),
                        $html->optgroup(
                            $html->each($options['functions'], fn($function) =>
                                $html->option($function)
                                    ->selected($columns[$curId]['fun'] == $function)
                            )
                        )
                        ->setLabel($this->trans->lang('Functions')),
                        $html->optgroup(
                            $html->each($options['grouping'], fn($grouping) =>
                                $html->option($grouping)
                                    ->selected($columns[$curId]['fun'] == $grouping)
                            )
                        )
                        ->setLabel($this->trans->lang('Aggregation')),
                    )
                    ->setName("column[$newId][fun]")
                )
                ->width(6),
                $html->formCol(
                    $html->formSelect(
                        $html->each($options['columns'], fn($column) =>
                            $html->option($column)
                                ->selected($columns[$curId]['col'] == $column)
                        )
                    )
                    ->setName("column[$newId][col]")
                )
                ->width(5),
                $html->formCol(
                    $html->checkbox()
                        ->checked(false)
                        ->setName("del[$newId]")
                        ->setClass("columns-item-checkbox")
                )
                ->width(1)
            );
            $newId++;
        }
        $html = $this->builder();
        return $html->build(...$formRows);
    }

    /**
     * @param string $formId
     *
     * @return string
     */
    public function editQueryColumns(string $formId): string
    {
        $rqColumns = rq(Columns::class);
        $html = $this->builder();
        return $html->build(
            $html->form(
                $this->editFormButtons($rqColumns, $formId),
                $html->div()
                    ->jxnBind($rqColumns)
            )
            ->responsive(true)->wrapped(false)->setId($formId)
        );
    }

    /**
     * @param array $values
     * @param array $options
     *
     * @return string
     */
    public function formQueryFilters(array $values, array $options): string
    {
        $wheres = $values['where'] ?? [];
        $count = count($wheres);
        $formRows = [];
        $html = $this->builder();

        for ($curId = 0, $newId = 0; $curId < $count; $curId++) {
            // Do not render deleted items
            if (isset($values['del'][$curId])) {
                continue;
            }

            $formRows[] = $html->formRow(
                $html->formCol(
                    $html->formSelect(
                        $html->option('(' . $this->trans->lang('anywhere') . ')'),
                        $html->each($options['columns'], fn($column) =>
                            $html->option($column)
                                ->selected($wheres[$curId]['col'] == $column)
                        )
                    )
                    ->setName("where[$newId][col]")
                )
                ->width(4),
                $html->formCol(
                    $html->formSelect(
                        $html->each($options['operators'], fn($operator) =>
                            $html->option($operator)
                                ->selected($wheres[$curId]['op'] == $operator)
                        )
                    )
                    ->setName("where[$newId][op]")
                )
                ->width(3),
                $html->formCol(
                    $html->formInput()
                        ->setName("where[$newId][val]")
                        ->setValue($wheres[$curId]['val'])
                )
                ->width(4),
                $html->formCol(
                    $html->checkbox()
                        ->checked(false)
                        ->setName("del[$newId]")
                        ->setClass("filters-item-checkbox")
                )
                ->width(1)
            );
            $newId++;
        }
        $html = $this->builder();
        return $html->build(...$formRows);
    }

    /**
     * @param string $formId
     *
     * @return string
     */
    public function editQueryFilters(string $formId): string
    {
        $rqFilters = rq(Filters::class);
        $html = $this->builder();
        return $html->build(
            $html->form(
                $this->editFormButtons($rqFilters, $formId),
                $html->div()
                    ->jxnBind($rqFilters)
            )
            ->responsive(true)->wrapped(false)->setId($formId)
        );
    }

    /**
     * @param array $values
     * @param array $options
     *
     * @return string
     */
    public function formQuerySorting(array $values, array $options): string
    {
        $orders = $values['order'] ?? [];
        $count = count($orders);
        $formRows = [];
        $html = $this->builder();

        for ($curId = 0, $newId = 0; $curId < $count; $curId++) {
            // Do not render deleted items
            if (isset($values['del'][$curId])) {
                continue;
            }

            $formRows[] = $html->formRow(
                $html->formCol(
                    $html->formSelect(
                        $html->each($options['columns'], fn($column) =>
                            $html->option($column)
                                ->selected($orders[$curId] == $column)
                        )
                    )
                    ->setName("order[]")
                )
                ->width(6),
                $html->formCol(
                    $html->inputGroup(
                        $html->text($this->trans->lang('descending')),
                        $html->checkbox()
                            ->checked(isset($values['desc'][$curId]))
                            ->setName("desc[$newId]")
                            ->setValue('1')
                    )
                )
                ->width(5),
                $html->formCol(
                    $html->checkbox()
                        ->checked(false)
                        ->setName("del[$newId]")
                        ->setClass("sorting-item-checkbox")
                )
                ->width(1)
            );
            $newId++;
        }
        $html = $this->builder();
        return $html->build(...$formRows);
    }

    /**
     * @param string $formId
     *
     * @return string
     */
    public function editQuerySorting(string $formId): string
    {
        $rqSorting = rq(Sorting::class);
        $html = $this->builder();
        return $html->build(
            $html->form(
                $this->editFormButtons($rqSorting, $formId),
                $html->div()
                    ->jxnBind($rqSorting)
            )
            ->responsive(true)->wrapped(false)
            ->setId($formId)
        );
    }

    /**
     * @param array $headers
     * @param array $rows
     *
     * @return string
     */
    public function selectResults(array $headers, array $rows): string
    {
        array_shift($headers);
        $html = $this->builder();
        return $html->build(
            $html->table(
                $html->thead(
                    $html->tr(
                        $html->each($headers, fn($header) =>
                            $html->th($header['key'] ?? '')
                        ),
                        $html->th(['style' => 'width:30px'])
                    )
                ),
                $html->tbody(
                    $html->each($rows, fn($row) =>
                        $html->tr(
                            $html->each($row['cols'], fn($col) =>
                                $html->td($col['value'])
                            ),
                            $html->td(['style' => 'width:30px'])
                        )
                    )
                ),
            )
            ->responsive(true)->style('bordered')
        );
    }

    /**
     * @param array $options
     * @param array $handlers
     *
     * @return string
     */
    public function selectOptions(array $options, array $handlers): string
    {
        $html = $this->builder();
        return $html->build(
            $html->formRow(
                $html->formCol(
                    $html->buttonGroup(
                        $html->button($this->html->text($this->trans->lang('Columns')))
                            ->outline()->secondary()->fullWidth()
                            ->jxnClick($handlers['btnColumns']),
                        $html->button($this->html->text($this->trans->lang('Filters')))
                            ->outline()->secondary()->fullWidth()
                            ->jxnClick($handlers['btnFilters']),
                        $html->button($this->html->text($this->trans->lang('Order')))
                            ->outline()->secondary()->fullWidth()
                            ->jxnClick($handlers['btnSorting'])
                    )
                    ->fullWidth(true)
                )
                ->width(6),
                $html->formCol(
                    $html->inputGroup(
                        $html->label(
                            $html->text($this->trans->lang('Limit'))
                        ),
                        $html->formInput()
                            ->setId($handlers['id']['limit'])
                            ->setType('number')
                            ->setName('limit')
                            ->setValue($options['limit']['value']),
                        $html->button()
                            ->outline()->secondary()->addIcon('ok')
                            ->jxnClick($handlers['btnLimit'])
                    )
                )
                ->width(3),
                $html->formCol(
                    $html->inputGroup(
                        $html->label(
                            $html->text($this->trans->lang('Text length'))
                        ),
                        $html->formInput()
                            ->setId($handlers['id']['length'])
                            ->setType('number')
                            ->setName('text_length')
                            ->setValue($options['length']['value']),
                        $html->button()
                            ->outline()->secondary()->addIcon('ok')
                            ->jxnClick($handlers['btnLength'])
                    )
                )
                ->width(3),
            )
        );
    }

    /**
     * @param array $ids
     * @param array $handlers
     *
     * @return string
     */
    public function tableSelect(array $ids, array $handlers): string
    {
        $html = $this->builder();
        return $html->build(
            $html->row(
                $html->col(
                    $html->form(
                        $html->div()
                            ->jxnBind(rq(Options::class)),
                        $html->formRow(
                            $html->formCol(
                                $html->pre()
                                    ->setId($ids['txtQueryId'])
                                    ->jxnBind(rq(QueryText::class))
                            )
                        ),
                    )
                    ->responsive(true)->wrapped(true)->setId($ids['formId'])
                )
                ->width(12)
            ),
            $html->row(
                $html->col(
                    $html->buttonGroup(
                        $html->button($this->html->text($this->trans->lang('Edit')))
                            ->outline()->secondary()->fullWidth()
                            ->jxnClick($handlers['btnEdit']),
                        $html->button($this->html->text($this->trans->lang('Execute')))
                            ->fullWidth()->secondary()
                            ->jxnClick($handlers['btnExec'])
                    )
                    ->fullWidth(true)
                )
                ->width(3),
                $html->col(
                    $html->nav()
                        ->jxnPagination(rq(Results::class))
                )
                ->width(9)
            ),
            $html->row(
                $html->col()
                    ->width(12)
                    ->jxnBind(rq(Results::class))
            )
        );
    }
}
