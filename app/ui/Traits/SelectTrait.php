<?php

namespace Lagdo\DbAdmin\Ui\Traits;

use Jaxon\Script\Call\JxnCall;
use Lagdo\DbAdmin\Ajax\App\Db\Table\Dql\Input\Columns;
use Lagdo\DbAdmin\Ajax\App\Db\Table\Dql\Input\Filters;
use Lagdo\DbAdmin\Ajax\App\Db\Table\Dql\Input\Sorting;
use Lagdo\DbAdmin\Ajax\App\Db\Table\Dql\Options;
use Lagdo\DbAdmin\Ajax\App\Db\Table\Dql\QueryText;
use Lagdo\DbAdmin\Ajax\App\Db\Table\Dql\Results;

use function array_shift;
use function count;
use function Jaxon\rq;
use function Jaxon\pm;

trait SelectTrait
{
    /**
     * @param JxnCall $rqInput
     * @param string $formId
     *
     * @return mixed
     */
    private function editFormButtons(JxnCall $rqInput, string $formId): mixed
    {
        return $this->html->formRow(
            $this->html->formCol()
                ->width(9)->addHtml('&nbsp;'), // Offset
            $this->html->formCol(
                $this->html->buttonGroup(
                    $this->html->button()->primary()
                        ->addIcon('plus')
                        ->jxnClick($rqInput->add(pm()->form($formId))),
                    $this->html->button()->danger()
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

        for ($curId = 0, $newId = 0; $curId < $count; $curId++) {
            // Do not render deleted items
            if (isset($values['del'][$curId])) {
                continue;
            }

            $formRows[] = $this->html->formRow(
                $this->html->formCol(
                    $this->html->formSelect(
                        $this->html->option('')->selected(false),
                        $this->html->optgroup(
                            $this->html->each($options['functions'], fn($function) =>
                                $this->html->option($function)
                                    ->selected($columns[$curId]['fun'] == $function)
                            )
                        )
                        ->setLabel($this->trans->lang('Functions')),
                        $this->html->optgroup(
                            $this->html->each($options['grouping'], fn($grouping) =>
                                $this->html->option($grouping)
                                    ->selected($columns[$curId]['fun'] == $grouping)
                            )
                        )
                        ->setLabel($this->trans->lang('Aggregation')),
                    )
                    ->setName("column[$newId][fun]")
                )
                ->width(6),
                $this->html->formCol(
                    $this->html->formSelect(
                        $this->html->each($options['columns'], fn($column) =>
                            $this->html->option($column)
                                ->selected($columns[$curId]['col'] == $column)
                        )
                    )
                    ->setName("column[$newId][col]")
                )
                ->width(5),
                $this->html->formCol(
                    $this->html->checkbox()
                        ->checked(false)
                        ->setName("del[$newId]")
                        ->setClass("columns-item-checkbox")
                )
                ->width(1)
            );
            $newId++;
        }
        return $this->html->build(...$formRows);
    }

    /**
     * @param string $formId
     *
     * @return string
     */
    public function editQueryColumns(string $formId): string
    {
        $rqColumns = rq(Columns::class);
        return $this->html->build(
            $this->html->form(
                $this->editFormButtons($rqColumns, $formId),
                $this->html->div()
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

        for ($curId = 0, $newId = 0; $curId < $count; $curId++) {
            // Do not render deleted items
            if (isset($values['del'][$curId])) {
                continue;
            }

            $formRows[] = $this->html->formRow(
                $this->html->formCol(
                    $this->html->formSelect(
                        $this->html->option('(' . $this->trans->lang('anywhere') . ')'),
                        $this->html->each($options['columns'], fn($column) =>
                            $this->html->option($column)
                                ->selected($wheres[$curId]['col'] == $column)
                        )
                    )
                    ->setName("where[$newId][col]")
                )
                ->width(4),
                $this->html->formCol(
                    $this->html->formSelect(
                        $this->html->each($options['operators'], fn($operator) =>
                            $this->html->option($operator)
                                ->selected($wheres[$curId]['op'] == $operator)
                        )
                    )
                    ->setName("where[$newId][op]")
                )
                ->width(3),
                $this->html->formCol(
                    $this->html->formInput()
                        ->setName("where[$newId][val]")
                        ->setValue($wheres[$curId]['val'])
                )
                ->width(4),
                $this->html->formCol(
                    $this->html->checkbox()
                        ->checked(false)
                        ->setName("del[$newId]")
                        ->setClass("filters-item-checkbox")
                )
                ->width(1)
            );
            $newId++;
        }
        return $this->html->build(...$formRows);
    }

    /**
     * @param string $formId
     *
     * @return string
     */
    public function editQueryFilters(string $formId): string
    {
        $rqFilters = rq(Filters::class);
        return $this->html->build(
            $this->html->form(
                $this->editFormButtons($rqFilters, $formId),
                $this->html->div()
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
        for ($curId = 0, $newId = 0; $curId < $count; $curId++) {
            // Do not render deleted items
            if (isset($values['del'][$curId])) {
                continue;
            }

            $formRows[] = $this->html->formRow(
                $this->html->formCol(
                    $this->html->formSelect(
                        $this->html->each($options['columns'], fn($column) =>
                            $this->html->option($column)
                                ->selected($orders[$curId] == $column)
                        )
                    )
                    ->setName("order[]")
                )
                ->width(6),
                $this->html->formCol(
                    $this->html->inputGroup(
                        $this->html->text()
                            ->addText($this->trans->lang('descending')),
                        $this->html->checkbox()
                            ->checked(isset($values['desc'][$curId]))
                            ->setName("desc[$newId]")
                            ->setValue('1')
                    )
                )
                ->width(5),
                $this->html->formCol(
                    $this->html->checkbox()
                        ->checked(false)
                        ->setName("del[$newId]")
                        ->setClass("sorting-item-checkbox")
                )
                ->width(1)
            );
            $newId++;
        }
        return $this->html->build(...$formRows);
    }

    /**
     * @param string $formId
     *
     * @return string
     */
    public function editQuerySorting(string $formId): string
    {
        $rqSorting = rq(Sorting::class);
        return $this->html->build(
            $this->html->form(
                $this->editFormButtons($rqSorting, $formId),
                $this->html->div()
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
        return $this->html->build(
            $this->html->table(
                $this->html->thead(
                    $this->html->tr(
                        $this->html->each($headers, fn($header) =>
                            $this->html->th($header['key'] ?? '')
                        ),
                        $this->html->th(['style' => 'width:30px'])
                    )
                ),
                $this->html->tbody(
                    $this->html->each($rows, fn($row) =>
                        $this->html->tr(
                            $this->html->each($row['cols'], fn($col) =>
                                $this->html->td($col['value'])
                            ),
                            $this->html->td(['style' => 'width:30px'])
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
        return $this->html->build(
            $this->html->formRow(
                $this->html->formCol(
                    $this->html->buttonGroup(
                        $this->html->button()
                            ->outline()->secondary()->fullWidth()
                            ->addText($this->trans->lang('Columns'))
                            ->jxnClick($handlers['btnColumns']),
                        $this->html->button()
                            ->outline()->secondary()->fullWidth()
                            ->addText($this->trans->lang('Filters'))
                            ->jxnClick($handlers['btnFilters']),
                        $this->html->button()
                            ->outline()->secondary()->fullWidth()
                            ->addText($this->trans->lang('Order'))
                            ->jxnClick($handlers['btnSorting'])
                    )
                    ->fullWidth(true)
                )
                ->width(6),
                $this->html->formCol(
                    $this->html->inputGroup(
                        $this->html->text()
                            ->addText($this->trans->lang('Limit')),
                        $this->html->formInput()
                            ->setId($handlers['id']['limit'])
                            ->setType('number')
                            ->setName('limit')
                            ->setValue($options['limit']['value']),
                        $this->html->button()
                            ->outline()->secondary()->addIcon('ok')
                            ->jxnClick($handlers['btnLimit'])
                    )
                )
                ->width(3),
                $this->html->formCol(
                    $this->html->inputGroup(
                        $this->html->text()
                            ->addText($this->trans->lang('Text length')),
                        $this->html->formInput()
                            ->setId($handlers['id']['length'])
                            ->setType('number')
                            ->setName('text_length')
                            ->setValue($options['length']['value']),
                        $this->html->button()
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
        return $this->html->build(
            $this->html->row(
                $this->html->col(
                    $this->html->form(
                        $this->html->div()
                            ->jxnBind(rq(Options::class)),
                        $this->html->formRow(
                            $this->html->formCol(
                                $this->html->pre()
                                    ->setId($ids['txtQueryId'])
                                    ->jxnBind(rq(QueryText::class))
                            )
                        ),
                    )
                    ->responsive(true)->wrapped(true)->setId($ids['formId'])
                )
                ->width(12)
            ),
            $this->html->row(
                $this->html->col(
                    $this->html->buttonGroup(
                        $this->html->button()
                            ->outline()->secondary()->fullWidth()
                            ->jxnClick($handlers['btnEdit'])
                            ->addText($this->trans->lang('Edit')),
                        $this->html->button()
                            ->fullWidth()->secondary()
                            ->jxnClick($handlers['btnExec'])
                            ->addText($this->trans->lang('Execute'))
                    )
                    ->fullWidth(true)
                )
                ->width(3),
                $this->html->col(
                    $this->html->nav()
                        ->jxnPagination(rq(Results::class))
                )
                ->width(9)
            ),
            $this->html->row(
                $this->html->col()
                    ->width(12)
                    ->jxnBind(rq(Results::class))
            )
        );
    }
}
