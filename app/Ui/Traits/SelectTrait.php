<?php

namespace Lagdo\DbAdmin\App\Ui\Traits;

use Lagdo\UiBuilder\BuilderInterface;
use Lagdo\UiBuilder\Jaxon\Builder;

use function array_shift;

trait SelectTrait
{
    /**
     * @param BuilderInterface $htmlBuilder
     * @param string $formId
     * @param string $btnAddOnClick
     * @param string $btnDelOnClick
     *
     * @return void
     */
    private function editFormButtons(BuilderInterface $htmlBuilder, string $formId,
        string $btnAddOnClick, string $btnDelOnClick)
    {
        $htmlBuilder
            ->formRow()
                ->formCol(9)->addHtml('&nbsp;') // Offset
                ->end()
                ->formCol(3)
                    ->buttonGroup(false)
                        ->button()->btnPrimary()->setId($formId . '-add')
                            ->setOnclick($btnAddOnClick)->addIcon('plus')
                        ->end()
                        ->button()->btnDanger()->setId($formId . '-del')
                            ->setOnclick($btnDelOnClick)->addIcon('remove')
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param BuilderInterface $htmlBuilder
     * @param string $rowId
     * @param string $formId
     * @param array $value
     * @param array $options
     *
     * @return void
     */
    private function editColumnValue(BuilderInterface $htmlBuilder, string $rowId,
        string $formId, array $value, array $options)
    {
        $htmlBuilder
            ->formRow()->setId("$formId-item-$rowId")
                ->formCol(6)
                    ->formSelect()->setName("columns[$rowId][fun]")
                        ->option(false, '')
                        ->end()
                        ->optgroup()->setLabel($this->utils->trans->lang('Functions'));
        foreach ($options['functions'] as $function) {
            $htmlBuilder
                            ->option($value['fun'] == $function, $function)
                            ->end();
        }
        $htmlBuilder
                        ->end()
                        ->optgroup()->setLabel($this->utils->trans->lang('Aggregation'));
        foreach ($options['grouping'] as $grouping) {
            $htmlBuilder
                            ->option($value['fun'] == $grouping, $grouping)
                            ->end();
        }
        $htmlBuilder
                        ->end()
                    ->end()
                ->end()
                ->formCol(5)
                    ->formSelect()->setName("columns[$rowId][col]");
        foreach ($options['columns'] as $column) {
            $htmlBuilder
                        ->option($value['col'] == $column, $column)
                        ->end();
        }
        $htmlBuilder
                    ->end()
                ->end()
                ->formCol(1)->setDataIndex($rowId)
                    ->checkbox(false)->setClass("$formId-item-checkbox")->setDataIndex($rowId)
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param string $formId
     * @param array $options
     * @param string $btnAddOnClick
     * @param string $btnDelOnClick
     *
     * @return string
     */
    public function editQueryColumns(string $formId, array $options, string $btnAddOnClick, string $btnDelOnClick): string
    {
        $htmlBuilder = Builder::new();
        $htmlBuilder
            ->form(true, false)->setId($formId);
        $this->editFormButtons($htmlBuilder, $formId, $btnAddOnClick, $btnDelOnClick);
        $i = 0;
        foreach ($options['values'] as $value) {
            $this->editColumnValue($htmlBuilder, $i, $formId, $value, $options);
            $i++;
        }
        $htmlBuilder
            ->end()
            // Empty line for new entry (must be outside the form)
            ->div()->setId("$formId-item-template")->setStyle('display:none');
        $this->editColumnValue($htmlBuilder, '__index__', $formId, ['fun' => '', 'col' => ''], $options);
        $htmlBuilder
            ->end();
        return $htmlBuilder->build();
    }

    /**
     * @param BuilderInterface $htmlBuilder
     * @param string $rowId
     * @param string $formId
     * @param array $value
     * @param array $options
     *
     * @return void
     */
    private function editFilterValue(BuilderInterface $htmlBuilder, string $rowId,
        string $formId, array $value, array $options)
    {
        $htmlBuilder
            ->formRow()->setId("$formId-item-$rowId")
                ->formCol(4)
                    ->formSelect()->setName("where[$rowId][col]")
                        ->option(false, '(' . $this->utils->trans->lang('anywhere') . ')')
                        ->end();
        foreach ($options['columns'] as $column) {
            $htmlBuilder
                        ->option($value['col'] == $column, $column)
                        ->end();
        }
        $htmlBuilder
                    ->end()
                ->end()
                ->formCol(2)
                    ->formSelect()->setName("where[$rowId][op]");
        foreach ($options['operators'] as $operator) {
            $htmlBuilder
                        ->option($value['op'] == $operator, $operator)
                        ->end();
        }
        $htmlBuilder
                    ->end()
                ->end()
                ->formCol(5)
                    ->formInput()->setName("where[$rowId][val]")->setValue($value['val'])
                    ->end()
                ->end()
                ->formCol(1)->setDataIndex($rowId)
                    ->checkbox(false)->setClass("$formId-item-checkbox")->setDataIndex($rowId)
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param string $formId
     * @param array $options
     * @param string $btnAddOnClick
     * @param string $btnDelOnClick
     *
     * @return string
     */
    public function editQueryFilters(string $formId, array $options, string $btnAddOnClick, string $btnDelOnClick): string
    {
        $htmlBuilder = Builder::new();
        $htmlBuilder
            ->form(true, false)->setId($formId);
        $this->editFormButtons($htmlBuilder, $formId, $btnAddOnClick, $btnDelOnClick);
        $i = 0;
        foreach ($options['values'] as $value) {
            $this->editFilterValue($htmlBuilder, $i, $formId, $value, $options);
            $i++;
        }
        $htmlBuilder
            ->end()
            // Empty line for new entry (must be outside the form)
            ->div()->setId("$formId-item-template")->setStyle('display:none');
        $this->editFilterValue($htmlBuilder, '__index__', $formId, ['col' => '', 'op' => '', 'val' => ''], $options);
        $htmlBuilder
            ->end();
        return $htmlBuilder->build();
    }

    /**
     * @param BuilderInterface $htmlBuilder
     * @param string $rowId
     * @param string $formId
     * @param array $value
     * @param array $options
     *
     * @return void
     */
    private function editSortingValue(BuilderInterface $htmlBuilder, string $rowId,
        string $formId, array $value, array $options)
    {
        $htmlBuilder
            ->formRow()->setId("$formId-item-$rowId")
                ->formCol(6)
                    ->formSelect()->setName("order[$rowId]");
        foreach ($options['columns'] as $column) {
            $htmlBuilder
                        ->option($value['col'] == $column, $column)
                        ->end();
        }
        $htmlBuilder
                    ->end()
                ->end()
                ->formCol(5)
                    ->inputGroup()
                        ->text()->addText($this->utils->trans->lang('descending'))
                        ->end()
                        ->checkbox($value['desc'])->setName("desc[$rowId]")->setValue('1')
                        ->end()
                    ->end()
                ->end()
                ->formCol(1)->setDataIndex($rowId)
                    ->checkbox(false)->setClass("$formId-item-checkbox")->setDataIndex($rowId)
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param string $formId
     * @param array $options
     * @param string $btnAddOnClick
     * @param string $btnDelOnClick
     *
     * @return string
     */
    public function editQuerySorting(string $formId, array $options, string $btnAddOnClick, string $btnDelOnClick): string
    {
        $htmlBuilder = Builder::new();
        $htmlBuilder
            ->form(true, false)->setId($formId);
        $this->editFormButtons($htmlBuilder, $formId, $btnAddOnClick, $btnDelOnClick);
        $i = 0;
        foreach ($options['values'] as $value) {
            $this->editSortingValue($htmlBuilder, $i, $formId, $value, $options);
            $i++;
        }
        $htmlBuilder
            ->end()
            // Empty line for new entry (must be outside the form)
            ->div()->setId("$formId-item-template")->setStyle('display:none');
        $this->editSortingValue($htmlBuilder, '__index__', $formId, ['col' => '', 'desc' => false], $options);
        $htmlBuilder
            ->end();
        return $htmlBuilder->build();
    }

    /**
     * @param array $headers
     * @param array $rows
     * @param string $btnEditRowClass
     * @param string $btnDeleteRowClass
     *
     * @return string
     */
    public function selectResults(array $headers, array $rows, string $btnEditRowClass, string $btnDeleteRowClass): string
    {
        $htmlBuilder = Builder::new();
        $htmlBuilder
            ->table(true, 'bordered')
                ->thead()
                    ->tr()
                        ->th(['style' => 'width:30px'])
                        ->end();
        array_shift($headers);
        foreach ($headers as $header) {
            $htmlBuilder
                        ->th($header['key'] ?? '')
                        ->end();
        }
        $htmlBuilder
                    ->end()
                ->end()
                ->tbody();
        $rowId = 0;
        foreach($rows as $row) {
            $htmlBuilder
                    ->tr()
                        ->td()
                            ->dropdown()
                                ->dropdownItem('primary')->addCaret()
                                ->end()
                                ->dropdownMenu()
                                    ->dropdownMenuItem()
                                        ->setClass($btnEditRowClass)->setDataRowId($rowId)->addIcon('edit')
                                    ->end()
                                    ->dropdownMenuItem()
                                        ->setClass($btnDeleteRowClass)->setDataRowId($rowId)->addIcon('remove')
                                    ->end()
                                ->end()
                            ->end()
                        ->end();
            foreach ($row['cols'] as $col) {
                $htmlBuilder
                        ->td($col['value'])
                        ->end();
            }
            $htmlBuilder
                    ->end();
            $rowId++;
        }
        $htmlBuilder
                ->end()
            ->end();
        return $htmlBuilder->build();
    }

    /**
     * @param array $ids
     * @param array $options
     *
     * @return string
     */
    public function tableSelect(array $ids, array $options): string
    {
        $htmlBuilder = Builder::new();
        $htmlBuilder
            ->row()
                ->col(12)
                    ->form(true, true)->setId($ids['formId'])
                        ->formRow()
                            ->formCol(6)
                                ->buttonGroup(true)
                                    ->button()->btnOutline()->btnFullWidth()
                                        ->setId($ids['btnColumnsId'])->addText($this->utils->trans->lang('Columns'))
                                    ->end()
                                    ->button()->btnOutline()->btnFullWidth()
                                        ->setId($ids['btnFiltersId'])->addText($this->utils->trans->lang('Filters'))
                                    ->end()
                                    ->button()->btnOutline()->btnFullWidth()
                                        ->setId($ids['btnSortingId'])->addText($this->utils->trans->lang('Order'))
                                    ->end()
                                ->end()
                            ->end()
                            ->formCol(3)
                                ->inputGroup()
                                    ->text()->addText($this->utils->trans->lang('Limit'))
                                    ->end()
                                    ->formInput()->setType('number')->setName('limit')->setValue($options['limit']['value'])
                                    ->end()
                                    ->button()->btnOutline()->setId($ids['btnLimitId'])->addIcon('ok')
                                    ->end()
                                ->end()
                            ->end()
                            ->formCol(3)
                                ->inputGroup()
                                    ->text()->addText($this->utils->trans->lang('Text length'))
                                    ->end()
                                    ->formInput()->setType('number')->setName('text_length')->setValue($options['length']['value'])
                                    ->end()
                                    ->button()->btnOutline()->setId($ids['btnLengthId'])->addIcon('ok')
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                        ->formRow()
                            ->formCol(9)
                                ->pre()->setId($ids['txtQueryId'])
                                ->end()
                            ->end()
                            ->formCol(3)
                                ->buttonGroup(true)
                                    ->button()->btnOutline()->btnFullWidth()
                                        ->setId($ids['btnEditId'])->addText($this->utils->trans->lang('Edit'))
                                    ->end()
                                    ->button()->btnFullWidth()->btnPrimary()
                                        ->setId($ids['btnExecId'])->addText($this->utils->trans->lang('Execute'))
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->col(12)->setId('adminer-table-select-pagination')
                ->end()
                ->col(12)->setId('adminer-table-select-results')
                ->end()
            ->end();
        return $htmlBuilder->build();
    }
}
