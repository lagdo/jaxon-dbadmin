<?php

namespace Lagdo\DbAdmin\Ui\Table;

use Jaxon\Script\Call\JxnCall;
use Lagdo\DbAdmin\Ajax\App\Db\Table\Dql\Duration;
use Lagdo\DbAdmin\Ajax\App\Db\Table\Dql\Options;
use Lagdo\DbAdmin\Ajax\App\Db\Table\Dql\QueryText;
use Lagdo\DbAdmin\Ajax\App\Db\Table\Dql\Results;
use Lagdo\DbAdmin\Ajax\App\Db\Table\Dql\Select;
use Lagdo\DbAdmin\Translator;
use Lagdo\UiBuilder\BuilderInterface;

use function array_shift;
use function count;
use function Jaxon\je;
use function Jaxon\rq;
use function sprintf;

class SelectUiBuilder
{
    /**
     * @param Translator $trans
     * @param BuilderInterface $ui
     */
    public function __construct(protected Translator $trans, protected BuilderInterface $ui)
    {}

    /**
     * @param JxnCall $rqInput
     * @param string $formId
     *
     * @return mixed
     */
    private function editFormButtons(JxnCall $rqInput, string $formId): mixed
    {
        return $this->ui->formRow(
            $this->ui->formCol($this->ui->html('&nbsp;'))
                ->width(9), // Offset
            $this->ui->formCol(
                $this->ui->buttonGroup(
                    $this->ui->button()->primary()
                        ->addIcon('plus')
                        ->jxnClick($rqInput->add(je($formId)->rd()->form())),
                    $this->ui->button()->danger()
                        ->addIcon('remove')
                        ->jxnClick($rqInput->del(je($formId)->rd()->form()))
                )
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
    public function formColumns(array $values, array $options): string
    {
        $columns = $values['column'] ?? [];
        $count = count($columns);
        $formRows = [];

        for ($curId = 0, $newId = 0; $curId < $count; $curId++) {
            // Do not render deleted items
            if (isset($values['del'][$curId])) {
                continue;
            }

            $formRows[] = $this->ui->formRow(
                $this->ui->formCol(
                    $this->ui->formSelect(
                        $this->ui->option('')->selected(false),
                        $this->ui->optgroup(
                            $this->ui->each($options['functions'], fn($function) =>
                                $this->ui->option($function)
                                    ->selected($columns[$curId]['fun'] == $function)
                            )
                        )
                        ->setLabel($this->trans->lang('Functions')),
                        $this->ui->optgroup(
                            $this->ui->each($options['grouping'], fn($grouping) =>
                                $this->ui->option($grouping)
                                    ->selected($columns[$curId]['fun'] == $grouping)
                            )
                        )
                        ->setLabel($this->trans->lang('Aggregation')),
                    )
                    ->setName("column[$newId][fun]")
                )
                ->width(6),
                $this->ui->formCol(
                    $this->ui->formSelect(
                        $this->ui->each($options['columns'], fn($column) =>
                            $this->ui->option($column)
                                ->selected($columns[$curId]['col'] == $column)
                        )
                    )
                    ->setName("column[$newId][col]")
                )
                ->width(5),
                $this->ui->formCol(
                    $this->ui->checkbox()
                        ->checked(false)
                        ->setName("del[$newId]")
                        ->setClass("columns-item-checkbox")
                )
                ->width(1)
            );
            $newId++;
        }
        return $this->ui->build(...$formRows);
    }

    /**
     * @param string $formId
     *
     * @return string
     */
    public function editColumns(string $formId): string
    {
        $rqColumns = rq(Options\Fields\Form\Columns::class);
        return $this->ui->build(
            $this->ui->form(
                $this->editFormButtons($rqColumns, $formId),
                $this->ui->div()
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
    public function formFilters(array $values, array $options): string
    {
        $wheres = $values['where'] ?? [];
        $count = count($wheres);
        $formRows = [];

        for ($curId = 0, $newId = 0; $curId < $count; $curId++) {
            // Do not render deleted items
            if (isset($values['del'][$curId])) {
                continue;
            }

            $formRows[] = $this->ui->formRow(
                $this->ui->formCol(
                    $this->ui->formSelect(
                        $this->ui->option('(' . $this->trans->lang('anywhere') . ')'),
                        $this->ui->each($options['columns'], fn($column) =>
                            $this->ui->option($column)
                                ->selected($wheres[$curId]['col'] == $column)
                        )
                    )
                    ->setName("where[$newId][col]")
                )
                ->width(4),
                $this->ui->formCol(
                    $this->ui->formSelect(
                        $this->ui->each($options['operators'], fn($operator) =>
                            $this->ui->option($operator)
                                ->selected($wheres[$curId]['op'] == $operator)
                        )
                    )
                    ->setName("where[$newId][op]")
                )
                ->width(3),
                $this->ui->formCol(
                    $this->ui->formInput()
                        ->setName("where[$newId][val]")
                        ->setValue($wheres[$curId]['val'])
                )
                ->width(4),
                $this->ui->formCol(
                    $this->ui->checkbox()
                        ->checked(false)
                        ->setName("del[$newId]")
                        ->setClass("filters-item-checkbox")
                )
                ->width(1)
            );
            $newId++;
        }
        return $this->ui->build(...$formRows);
    }

    /**
     * @param string $formId
     *
     * @return string
     */
    public function editFilters(string $formId): string
    {
        $rqFilters = rq(Options\Fields\Form\Filters::class);
        return $this->ui->build(
            $this->ui->form(
                $this->editFormButtons($rqFilters, $formId),
                $this->ui->div()
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
    public function formSorting(array $values, array $options): string
    {
        $orders = $values['order'] ?? [];
        $count = count($orders);
        $formRows = [];

        for ($curId = 0, $newId = 0; $curId < $count; $curId++) {
            // Do not render deleted items
            if (isset($values['del'][$curId])) {
                continue;
            }

            $formRows[] = $this->ui->formRow(
                $this->ui->formCol(
                    $this->ui->formSelect(
                        $this->ui->each($options['columns'], fn($column) =>
                            $this->ui->option($column)
                                ->selected($orders[$curId] == $column)
                        )
                    )
                    ->setName("order[]")
                )
                ->width(6),
                $this->ui->formCol(
                    $this->ui->inputGroup(
                        $this->ui->label(
                            $this->ui->text($this->trans->lang('descending'))
                        ),
                        $this->ui->checkbox()
                            ->checked(isset($values['desc'][$curId]))
                            ->setName("desc[$newId]")
                            ->setValue('1')
                    )
                )
                ->width(5),
                $this->ui->formCol(
                    $this->ui->checkbox()
                        ->checked(false)
                        ->setName("del[$newId]")
                        ->setClass("sorting-item-checkbox")
                )
                ->width(1)
            );
            $newId++;
        }
        return $this->ui->build(...$formRows);
    }

    /**
     * @param string $formId
     *
     * @return string
     */
    public function editSorting(string $formId): string
    {
        $rqSorting = rq(Options\Fields\Form\Sorting::class);
        return $this->ui->build(
            $this->ui->form(
                $this->editFormButtons($rqSorting, $formId),
                $this->ui->div()
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
    public function results(array $headers, array $rows): string
    {
        array_shift($headers);
        return $this->ui->build(
            $this->ui->table(
                $this->ui->thead(
                    $this->ui->tr(
                        $this->ui->each($headers, fn($header) =>
                            $this->ui->th($header['title'] ?? '')
                        ),
                        $this->ui->th(['style' => 'width:30px'])
                    )
                ),
                $this->ui->tbody(
                    $this->ui->each($rows, fn($row) =>
                        $this->ui->tr(
                            $this->ui->each($row['cols'], fn($col) =>
                                $this->ui->td($col['value'])
                            ),
                            $this->ui->td(['style' => 'width:30px'])
                        )
                    )
                ),
            )
            ->responsive(true)->style('bordered')
        );
    }

    /**
     * @param array $options
     *
     * @return string
     */
    public function optionsFields(array $options): string
    {
        $columnCount = count($options['columns']['column'] ?? []);
        $filterCount = count($options['filters']['where'] ?? []);
        $sortingCount = count($options['sorting']['order'] ?? []);
        return $this->ui->build(
            $this->ui->buttonGroup(
                $this->ui->button(
                    $this->ui->text($this->trans->lang('Columns ')),
                    $this->ui->when($columnCount > 0, fn() =>
                        $this->ui->badge((string)$columnCount)->type('secondary'))
                )
                    ->outline()->secondary()->fullWidth()
                    ->jxnClick(rq(Options\Fields\Columns::class)->edit()),
                $this->ui->button(
                    $this->ui->text($this->trans->lang('Filters ')),
                    $this->ui->when($filterCount > 0, fn() =>
                        $this->ui->badge((string)$filterCount)->type('secondary'))
                )
                    ->outline()->secondary()->fullWidth()
                    ->jxnClick(rq(Options\Fields\Filters::class)->edit()),
                $this->ui->button(
                    $this->ui->text($this->trans->lang('Order ')),
                    $this->ui->when($sortingCount > 0, fn() =>
                        $this->ui->badge((string)$sortingCount)->type('secondary'))
                )
                    ->outline()->secondary()->fullWidth()
                    ->jxnClick(rq(Options\Fields\Sorting::class)->edit())
            )
            ->fullWidth()
        );
    }

    /**
     * @param array $options
     *
     * @return string
     */
    public function optionsValues(array $options): string
    {
        $optionsLimitId = 'dbadmin-table-select-options-form-limit';
        $optionsLengthId = 'dbadmin-table-select-options-form-length';
        $rqOptionsValues = rq(Options\Values::class);

        return $this->ui->build(
            $this->ui->formRow(
                $this->ui->formCol(
                    $this->ui->inputGroup(
                        $this->ui->label(
                            $this->ui->text($this->trans->lang('Limit'))
                        ),
                        $this->ui->formInput()
                            ->setId($optionsLimitId)
                            ->setType('number')
                            ->setName('limit')
                            ->setValue($options['limit']),
                        $this->ui->button()
                            ->outline()->secondary()->addIcon('ok')
                            ->jxnClick($rqOptionsValues
                                ->saveSelectLimit(je($optionsLimitId)->rd()->input()->toInt()))
                    )
                )
                ->width(5),
                $this->ui->formCol(
                    $this->ui->inputGroup(
                        $this->ui->label(
                            $this->ui->text($this->trans->lang('Text length'))
                        ),
                        $this->ui->formInput()
                            ->setId($optionsLengthId)
                            ->setType('number')
                            ->setName('text_length')
                            ->setValue($options['length']),
                        $this->ui->button()
                            ->outline()->secondary()->addIcon('ok')
                            ->jxnClick($rqOptionsValues
                                ->saveTextLength(je($optionsLengthId)->rd()->input()->toInt()))
                    )
                )
                ->width(7)
            )
        );
    }

    /**
     * @param array $ids
     *
     * @return string
     */
    public function table(array $ids): string
    {
        return $this->ui->build(
            $this->ui->row(
                $this->ui->col(
                    $this->ui->form(
                        $this->ui->div(
                            $this->ui->formRow(
                                $this->ui->formCol()->width(6)
                                    ->jxnBind(rq(Options\Fields::class)),
                                $this->ui->formCol()->width(6)
                                    ->jxnBind(rq(Options\Values::class))
                            )
                        ),
                        $this->ui->formRow(
                            $this->ui->formCol(
                                $this->ui->pre()
                                    ->setId($ids['txtQueryId'])
                                    ->jxnBind(rq(QueryText::class))
                            )
                        ),
                    )
                    ->responsive(true)->wrapped(true)->setId($ids['formId'])
                )
                ->width(12)
            ),
            $this->ui->row(
                $this->ui->col(
                    $this->ui->buttonGroup(
                        $this->ui->button($this->ui->text($this->trans->lang('Edit')))
                            ->outline()->secondary()->fullWidth()
                            ->jxnClick(rq(Select::class)->edit()),
                        $this->ui->button($this->ui->text($this->trans->lang('Execute')))
                            ->fullWidth()->primary()
                            ->jxnClick(rq(Results::class)->page())
                    )
                    ->fullWidth(true)
                )
                ->width(3),
                $this->ui->col(
                    $this->ui->row(
                        $this->ui->col(
                            $this->ui->nav()
                                ->jxnPagination(rq(Results::class))
                        )
                        ->width(10)
                        ->setStyle('overflow:hidden'),
                        $this->ui->col()
                            ->width(2)
                            ->jxnBind(rq(Duration::class))
                    )
                )
                ->width(9),
            ),
            $this->ui->row(
                $this->ui->col()
                    ->width(12)
                    ->jxnBind(rq(Results::class))
            )
        );
    }

    /**
     * @param float $duration
     *
     * @return string
     */
    public function duration(float $duration): string
    {
        return $this->ui->build(
            $this->ui->inputGroup(
                $this->ui->label(sprintf('%.4f&nbsp;s', $duration))
            )
        );
    }
}
