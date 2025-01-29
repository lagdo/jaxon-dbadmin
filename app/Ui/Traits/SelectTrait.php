<?php

namespace Lagdo\DbAdmin\App\Ui\Traits;

use Jaxon\Script\JxnCall;
use Lagdo\DbAdmin\App\Ajax\Db\Table\Dql\Input\Columns;
use Lagdo\DbAdmin\App\Ajax\Db\Table\Dql\Input\Filters;
use Lagdo\DbAdmin\App\Ajax\Db\Table\Dql\Input\Sorting;
use Lagdo\DbAdmin\App\Ajax\Db\Table\Dql\Options;
use Lagdo\DbAdmin\App\Ajax\Db\Table\Dql\QueryText;
use Lagdo\DbAdmin\App\Ajax\Db\Table\Dql\Results;
use Lagdo\UiBuilder\BuilderInterface;
use Lagdo\UiBuilder\Jaxon\Builder;

use function array_shift;
use function count;
use function Jaxon\rq;
use function Jaxon\pm;

trait SelectTrait
{
    /**
     * @param BuilderInterface $htmlBuilder
     * @param JxnCall $rqInput
     * @param string $formId
     *
     * @return void
     */
    private function editFormButtons(BuilderInterface $htmlBuilder, JxnCall $rqInput, string $formId)
    {
        $htmlBuilder
            ->formRow()
                ->formCol(9)->addHtml('&nbsp;') // Offset
                ->end()
                ->formCol(3)
                    ->buttonGroup(false)
                        ->button()->btnPrimary()
                            ->addIcon('plus')
                            ->jxnClick($rqInput->add(pm()->form($formId)))
                        ->end()
                        ->button()->btnDanger()
                            ->addIcon('remove')
                            ->jxnClick($rqInput->del(pm()->form($formId)))
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param array $values
     * @param array $options
     *
     * @return string
     */
    public function formQueryColumns(array $values, array $options): string
    {
        $htmlBuilder = Builder::new();
        $columns = $values['column'] ?? [];
        $count = count($columns);
        for ($curId = 0, $newId = 0; $curId < $count; $curId++) {
            // Do not render deleted items
            if (isset($values['del'][$curId])) {
                continue;
            }
            $htmlBuilder
                ->formRow()
                    ->formCol(6)
                        ->formSelect()->setName("column[$newId][fun]")
                            ->option(false, '')
                            ->end()
                            ->optgroup()->setLabel($this->trans->lang('Functions'));
            foreach ($options['functions'] as $function) {
                $htmlBuilder
                                ->option($columns[$curId]['fun'] == $function, $function)
                                ->end();
            }
            $htmlBuilder
                            ->end()
                            ->optgroup()->setLabel($this->trans->lang('Aggregation'));
            foreach ($options['grouping'] as $grouping) {
                $htmlBuilder
                                ->option($columns[$curId]['fun'] == $grouping, $grouping)
                                ->end();
            }
            $htmlBuilder
                            ->end()
                        ->end()
                    ->end()
                    ->formCol(5)
                        ->formSelect()->setName("column[$newId][col]");
            foreach ($options['columns'] as $column) {
                $htmlBuilder
                            ->option($columns[$curId]['col'] == $column, $column)
                            ->end();
            }
            $htmlBuilder
                        ->end()
                    ->end()
                    ->formCol(1)
                        ->checkbox(false)
                            ->setName("del[$newId]")
                            ->setClass("columns-item-checkbox")
                        ->end()
                    ->end()
                ->end();
            $newId++;
        }
        return $htmlBuilder->build();
    }

    /**
     * @param string $formId
     *
     * @return string
     */
    public function editQueryColumns(string $formId): string
    {
        $rqColumns = rq(Columns::class);
        $htmlBuilder = Builder::new();
        $htmlBuilder
            ->form(true, false)
                ->setId($formId);
        $this->editFormButtons($htmlBuilder, $rqColumns, $formId);
        $htmlBuilder
                ->div()->jxnBind($rqColumns)
                ->end()
            ->end();
        return $htmlBuilder->build();
    }

    /**
     * @param array $values
     * @param array $options
     *
     * @return string
     */
    public function formQueryFilters(array $values, array $options): string
    {
        $htmlBuilder = Builder::new();
        $wheres = $values['where'] ?? [];
        $count = count($wheres);
        for ($curId = 0, $newId = 0; $curId < $count; $curId++) {
            // Do not render deleted items
            if (isset($values['del'][$curId])) {
                continue;
            }
            $htmlBuilder
                ->formRow()
                    ->formCol(4)
                        ->formSelect()->setName("where[$newId][col]")
                            ->option(false, '(' . $this->trans->lang('anywhere') . ')')
                            ->end();
            foreach ($options['columns'] as $column) {
                $htmlBuilder
                            ->option($wheres[$curId]['col'] == $column, $column)
                            ->end();
            }
            $htmlBuilder
                        ->end()
                    ->end()
                    ->formCol(3)
                        ->formSelect()->setName("where[$newId][op]");
            foreach ($options['operators'] as $operator) {
                $htmlBuilder
                            ->option($wheres[$curId]['op'] == $operator, $operator)
                            ->end();
            }
            $htmlBuilder
                        ->end()
                    ->end()
                    ->formCol(4)
                        ->formInput()
                            ->setName("where[$newId][val]")
                            ->setValue($wheres[$curId]['val'])
                        ->end()
                    ->end()
                    ->formCol(1)
                        ->checkbox(false)
                            ->setName("del[$newId]")
                            ->setClass("filters-item-checkbox")
                        ->end()
                    ->end()
                ->end();
            $newId++;
        }
        return $htmlBuilder->build();
    }

    /**
     * @param string $formId
     *
     * @return string
     */
    public function editQueryFilters(string $formId): string
    {
        $rqFilters = rq(Filters::class);
        $htmlBuilder = Builder::new();
        $htmlBuilder
            ->form(true, false)
                ->setId($formId);
        $this->editFormButtons($htmlBuilder, $rqFilters, $formId);
        $htmlBuilder
                ->div()->jxnBind($rqFilters)
                ->end()
            ->end();
        return $htmlBuilder->build();
    }

    /**
     * @param array $values
     * @param array $options
     *
     * @return string
     */
    public function formQuerySorting(array $values, array $options): string
    {
        $htmlBuilder = Builder::new();
        $orders = $values['order'] ?? [];
        $count = count($orders);
        for ($curId = 0, $newId = 0; $curId < $count; $curId++) {
            // Do not render deleted items
            if (isset($values['del'][$curId])) {
                continue;
            }
            $htmlBuilder
                ->formRow()
                    ->formCol(6)
                        ->formSelect()->setName("order[]");
            foreach ($options['columns'] as $column) {
                $htmlBuilder
                            ->option($orders[$curId] == $column, $column)
                            ->end();
            }
            $htmlBuilder
                        ->end()
                    ->end()
                    ->formCol(5)
                        ->inputGroup()
                            ->text()
                                ->addText($this->trans->lang('descending'))
                            ->end()
                            ->checkbox(isset($values['desc'][$curId]))
                                ->setName("desc[$newId]")
                                ->setValue('1')
                            ->end()
                        ->end()
                    ->end()
                    ->formCol(1)
                        ->checkbox(false)
                            ->setName("del[$newId]")
                            ->setClass("sorting-item-checkbox")
                        ->end()
                    ->end()
                ->end();
            $newId++;
        }
        return $htmlBuilder->build();
    }

    /**
     * @param string $formId
     *
     * @return string
     */
    public function editQuerySorting(string $formId): string
    {
        $rqSorting = rq(Sorting::class);
        $htmlBuilder = Builder::new();
        $htmlBuilder
            ->form(true, false)
                ->setId($formId);
        $this->editFormButtons($htmlBuilder, $rqSorting, $formId);
        $htmlBuilder
                ->div()->jxnBind($rqSorting)
                ->end()
            ->end();
        return $htmlBuilder->build();
    }

    /**
     * @param array $headers
     * @param array $rows
     *
     * @return string
     */
    public function selectResults(array $headers, array $rows): string
    {
        $htmlBuilder = Builder::new();
        $htmlBuilder
            ->table(true, 'bordered')
                ->thead()
                    ->tr();
        array_shift($headers);
        foreach ($headers as $header) {
            $htmlBuilder
                        ->th($header['key'] ?? '')
                        ->end();
        }
        $htmlBuilder
                        ->th(['style' => 'width:30px'])
                        ->end()
                    ->end()
                ->end()
                ->tbody();
        $rowId = 0;
        foreach($rows as $row) {
            $htmlBuilder
                    ->tr();
            foreach ($row['cols'] as $col) {
                $htmlBuilder
                        ->td($col['value'])
                        ->end();
            }
            $htmlBuilder
                        ->td(['style' => 'width:30px'])
                        ->end()
                    ->end();
            $rowId++;
        }
        $htmlBuilder
                ->end()
            ->end();
        return $htmlBuilder->build();
    }

    /**
     * @param array $options
     * @param array $handlers
     *
     * @return string
     */
    public function selectOptions(array $options, array $handlers): string
    {
        $htmlBuilder = Builder::new();
        $htmlBuilder
            ->formRow()
                ->formCol(6)
                    ->buttonGroup(true)
                        ->button()->btnOutline()->btnSecondary()->btnFullWidth()
                            ->jxnClick($handlers['btnColumns'])->addText($this->trans->lang('Columns'))
                        ->end()
                        ->button()->btnOutline()->btnSecondary()->btnFullWidth()
                            ->jxnClick($handlers['btnFilters'])->addText($this->trans->lang('Filters'))
                        ->end()
                        ->button()->btnOutline()->btnSecondary()->btnFullWidth()
                            ->jxnClick($handlers['btnSorting'])->addText($this->trans->lang('Order'))
                        ->end()
                    ->end()
                ->end()
                ->formCol(3)
                    ->inputGroup()
                        ->text()
                            ->addText($this->trans->lang('Limit'))
                        ->end()
                        ->formInput()
                            ->setId($handlers['id']['limit'])
                            ->setType('number')
                            ->setName('limit')
                            ->setValue($options['limit']['value'])
                        ->end()
                        ->button()->btnOutline()->btnSecondary()
                            ->jxnClick($handlers['btnLimit'])->addIcon('ok')
                        ->end()
                    ->end()
                ->end()
                ->formCol(3)
                    ->inputGroup()
                        ->text()->addText($this->trans->lang('Text length'))
                        ->end()
                        ->formInput()
                            ->setId($handlers['id']['length'])
                            ->setType('number')
                            ->setName('text_length')
                            ->setValue($options['length']['value'])
                        ->end()
                        ->button()->btnOutline()->btnSecondary()
                            ->jxnClick($handlers['btnLength'])->addIcon('ok')
                        ->end()
                    ->end()
                ->end()
            ->end();
        return $htmlBuilder->build();
    }

    /**
     * @param array $ids
     * @param array $handlers
     *
     * @return string
     */
    public function tableSelect(array $ids, array $handlers): string
    {
        $htmlBuilder = Builder::new();
        $htmlBuilder
            ->row()
                ->col(12)
                    ->form(true, true)->setId($ids['formId'])
                        ->div()->jxnBind(rq(Options::class))
                        ->end()
                        ->formRow()
                            ->formCol()
                                ->pre()->setId($ids['txtQueryId'])->jxnBind(rq(QueryText::class))
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->row()
                ->col(3)
                    ->buttonGroup(true)
                        ->button()->btnOutline()->btnSecondary()->btnFullWidth()
                            ->jxnClick($handlers['btnEdit'])
                            ->addText($this->trans->lang('Edit'))
                        ->end()
                        ->button()->btnFullWidth()->btnSecondary()
                            ->jxnClick($handlers['btnExec'])
                            ->addText($this->trans->lang('Execute'))
                        ->end()
                    ->end()
                ->end()
                ->col(9)
                    ->nav()
                        ->jxnPagination(rq(Results::class))
                    ->end()
                ->end()
            ->end()
            ->row()
                ->col(12)
                    ->jxnBind(rq(Results::class))
                ->end()
            ->end();
        return $htmlBuilder->build();
    }
}
