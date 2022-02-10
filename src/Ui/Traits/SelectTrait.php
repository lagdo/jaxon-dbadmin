<?php

namespace Lagdo\DbAdmin\Ui\Traits;

use Lagdo\UiBuilder\AbstractBuilder;

use function array_shift;

trait SelectTrait
{
    /**
     * @param array $values
     *
     * @return void
     */
    private function _showQueryColumns(array $values)
    {
        $i = 0;
        foreach ($values as $value) {
            $this->htmlBuilder
                ->input()->setName("columns[$i][fun]")->setValue($value['fun'])
                ->endShorted()
                ->input()->setName("columns[$i][col]")->setValue($value['col'])
                ->endShorted();
            $i++;
        }
    }

    /**
     * @param array $values
     *
     * @return string
     */
    public function showQueryColumns(array $values): string
    {
        $this->htmlBuilder->clear();
        $this->_showQueryColumns($values);
        return $this->htmlBuilder->build();
    }

    /**
     * @param string $formId
     * @param string $btnAddOnClick
     * @param string $btnDelOnClick
     *
     * @return void
     */
    private function editFormButtons(string $formId, string $btnAddOnClick, string $btnDelOnClick)
    {
        $this->htmlBuilder
            ->formRow()
                ->formCol(9)->addHtml('&nbsp;') // Offset
                ->end()
                ->formCol(3)
                    ->buttonGroup(false)
                        ->button(AbstractBuilder::BTN_PRIMARY)->setId($formId . '-add')
                            ->setOnclick($btnAddOnClick)->addIcon('plus')
                        ->end()
                        ->button(AbstractBuilder::BTN_DANGER)->setId($formId . '-del')
                            ->setOnclick($btnDelOnClick)->addIcon('remove')
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param string $rowId
     * @param string $formId
     * @param array $value
     * @param array $options
     *
     * @return void
     */
    private function editColumnValue(string $rowId, string $formId, array $value, array $options)
    {
        $this->htmlBuilder
            ->formRow()->setId("$formId-item-$rowId")
                ->formCol(6)
                    ->formSelect()->setName("columns[$rowId][fun]")
                        ->option(false, '')
                        ->end()
                        ->optgroup()->setLabel($this->trans->lang('Functions'));
        foreach ($options['functions'] as $function) {
            $this->htmlBuilder
                            ->option($value['fun'] == $function, $function)
                            ->end();
        }
        $this->htmlBuilder
                        ->end()
                        ->optgroup()->setLabel($this->trans->lang('Aggregation'));
        foreach ($options['grouping'] as $grouping) {
            $this->htmlBuilder
                            ->option($value['fun'] == $grouping, $grouping)
                            ->end();
        }
        $this->htmlBuilder
                        ->end()
                    ->end()
                ->end()
                ->formCol(5)
                    ->formSelect()->setName("columns[$rowId][col]");
        foreach ($options['columns'] as $column) {
            $this->htmlBuilder
                        ->option($value['col'] == $column, $column)
                        ->end();
        }
        $this->htmlBuilder
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
        $this->htmlBuilder->clear()
            ->form(true, false)->setId($formId);
        $this->editFormButtons($formId, $btnAddOnClick, $btnDelOnClick);
        $i = 0;
        foreach ($options['values'] as $value) {
            $this->editColumnValue($i, $formId, $value, $options);
            $i++;
        }
        $this->htmlBuilder
            ->end()
            // Empty line for new entry (must be outside the form)
            ->div()->setId("$formId-item-template")->setStyle('display:none');
        $this->editColumnValue('__index__', $formId, ['fun' => '', 'col' => ''], $options);
        $this->htmlBuilder
            ->end();
        return $this->htmlBuilder->build();
    }

    /**
     * @param array $values
     *
     * @return void
     */
    private function _showQueryFilters(array $values)
    {
        $i = 0;
        foreach ($values as $value) {
            $this->htmlBuilder
                ->input()->setName("where[$i][col]")->setValue($value['col'])
                ->endShorted()
                ->input()->setName("where[$i][op]")->setValue($value['op'])
                ->endShorted()
                ->input()->setName("where[$i][val]")->setValue($value['val'])
                ->endShorted();
            $i++;
        }
    }

    /**
     * @param array $values
     *
     * @return string
     */
    public function showQueryFilters(array $values): string
    {
        $this->htmlBuilder->clear();
        $this->_showQueryFilters($values);
        return $this->htmlBuilder->build();
    }

    /**
     * @param string $rowId
     * @param string $formId
     * @param array $value
     * @param array $options
     *
     * @return void
     */
    private function editFilterValue(string $rowId, string $formId, array $value, array $options)
    {
        $this->htmlBuilder
            ->formRow()->setId("$formId-item-$rowId")
                ->formCol(4)
                    ->formSelect()->setName("where[$rowId][col]")
                        ->option(false, '(' . $this->trans->lang('anywhere') . ')')
                        ->end();
        foreach ($options['columns'] as $column) {
            $this->htmlBuilder
                        ->option($value['col'] == $column, $column)
                        ->end();
        }
        $this->htmlBuilder
                    ->end()
                ->end()
                ->formCol(2)
                    ->formSelect()->setName("where[$rowId][op]");
        foreach ($options['operators'] as $operator) {
            $this->htmlBuilder
                        ->option($value['op'] == $operator, $operator)
                        ->end();
        }
        $this->htmlBuilder
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
        $this->htmlBuilder->clear()
            ->form(true, false)->setId($formId);
        $this->editFormButtons($formId, $btnAddOnClick, $btnDelOnClick);
        $i = 0;
        foreach ($options['values'] as $value) {
            $this->editFilterValue($i, $formId, $value, $options);
            $i++;
        }
        $this->htmlBuilder
            ->end()
            // Empty line for new entry (must be outside the form)
            ->div()->setId("$formId-item-template")->setStyle('display:none');
        $this->editFilterValue('__index__', $formId, ['col' => '', 'op' => '', 'val' => ''], $options);
        $this->htmlBuilder
            ->end();
        return $this->htmlBuilder->build();
    }

    /**
     * @param array $values
     *
     * @return void
     */
    private function _showQuerySorting(array $values)
    {
        $i = 0;
        foreach ($values as $value) {
            $this->htmlBuilder
                ->input()->setName("order[$i]")->setValue($value['col'])
                ->endShorted()
                ->input()->setName("desc[$i]")->setValue($value['desc'])
                ->endShorted();
            $i++;
        }
    }

    /**
     * @param array $values
     *
     * @return string
     */
    public function showQuerySorting(array $values): string
    {
        $this->htmlBuilder->clear();
        $this->_showQuerySorting($values);
        return $this->htmlBuilder->build();
    }

    /**
     * @param string $rowId
     * @param string $formId
     * @param array $value
     * @param array $options
     *
     * @return void
     */
    private function editSortingValue(string $rowId, string $formId, array $value, array $options)
    {
        $this->htmlBuilder
            ->formRow()->setId("$formId-item-$rowId")
                ->formCol(6)
                    ->formSelect()->setName("order[$rowId]");
        foreach ($options['columns'] as $column) {
            $this->htmlBuilder
                        ->option($value['col'] == $column, $column)
                        ->end();
        }
        $this->htmlBuilder
                    ->end()
                ->end()
                ->formCol(5)
                    ->inputGroup()
                        ->text()->addText($this->trans->lang('descending'))
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
        $this->htmlBuilder->clear()
            ->form(true, false)->setId($formId);
        $this->editFormButtons($formId, $btnAddOnClick, $btnDelOnClick);
        $i = 0;
        foreach ($options['values'] as $value) {
            $this->editSortingValue($i, $formId, $value, $options);
            $i++;
        }
        $this->htmlBuilder
            ->end()
            // Empty line for new entry (must be outside the form)
            ->div()->setId("$formId-item-template")->setStyle('display:none');
        $this->editSortingValue('__index__', $formId, ['col' => '', 'desc' => false], $options);
        $this->htmlBuilder
            ->end();
        return $this->htmlBuilder->build();
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
        $this->htmlBuilder->clear()
            ->table(true, 'bordered')
                ->thead()
                    ->tr()
                        ->th(['style' => 'width:30px'])
                        ->end();
        array_shift($headers);
        foreach ($headers as $header) {
            $this->htmlBuilder
                        ->th($header['key'] ?? '')
                        ->end();
        }
        $this->htmlBuilder
                    ->end()
                ->end()
                ->tbody();
        $rowId = 0;
        foreach($rows as $row) {
            $this->htmlBuilder
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
                $this->htmlBuilder
                        ->td($col['value'])
                        ->end();
            }
            $this->htmlBuilder
                    ->end();
            $rowId++;
        }
        $this->htmlBuilder
                ->end()
            ->end();
        return $this->htmlBuilder->build();
    }

    /**
     * @param array $ids
     * @param array $options
     *
     * @return string
     */
    public function tableSelect(array $ids, array $options): string
    {
        $this->htmlBuilder->clear()
            ->row()
                ->col(12)
                    ->form(true, true)->setId($ids['formId'])
                        ->div()->setStyle('display:none')
                            ->div()->setId('adminer-table-select-columns-show');
        $this->_showQueryColumns($options['columns']['values']);
        $this->htmlBuilder
                            ->end()
                            ->div()->setId('adminer-table-select-filters-show');
        $this->_showQueryFilters($options['filters']['values']);
        $this->htmlBuilder
                            ->end()
                            ->div()->setId('adminer-table-select-sorting-show');
        $this->_showQuerySorting($options['sorting']['values']);
        $this->htmlBuilder
                            ->end()
                        ->end()
                        ->formRow()
                            ->formCol(6)
                                ->buttonGroup(true)
                                    ->button(AbstractBuilder::BTN_OUTLINE + AbstractBuilder::BTN_FULL_WIDTH)
                                        ->setId($ids['btnColumnsId'])->addText($this->trans->lang('Columns'))
                                    ->end()
                                    ->button(AbstractBuilder::BTN_OUTLINE + AbstractBuilder::BTN_FULL_WIDTH)
                                        ->setId($ids['btnFiltersId'])->addText($this->trans->lang('Filters'))
                                    ->end()
                                    ->button(AbstractBuilder::BTN_OUTLINE + AbstractBuilder::BTN_FULL_WIDTH)
                                        ->setId($ids['btnSortingId'])->addText($this->trans->lang('Order'))
                                    ->end()
                                ->end()
                            ->end()
                            ->formCol(3)
                                ->inputGroup()
                                    ->text()->addText($this->trans->lang('Limit'))
                                    ->end()
                                    ->formInput()->setType('number')->setName('limit')->setValue($options['limit']['value'])
                                    ->end()
                                    ->button(AbstractBuilder::BTN_OUTLINE)->setId($ids['btnLimitId'])->addIcon('ok')
                                    ->end()
                                ->end()
                            ->end()
                            ->formCol(3)
                                ->inputGroup()
                                    ->text()->addText($this->trans->lang('Text length'))
                                    ->end()
                                    ->formInput()->setType('number')->setName('text_length')->setValue($options['length']['value'])
                                    ->end()
                                    ->button(AbstractBuilder::BTN_OUTLINE)->setId($ids['btnLengthId'])->addIcon('ok')
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
                                    ->button(AbstractBuilder::BTN_OUTLINE + AbstractBuilder::BTN_FULL_WIDTH)
                                        ->setId($ids['btnEditId'])->addText($this->trans->lang('Edit'))
                                    ->end()
                                    ->button(AbstractBuilder::BTN_PRIMARY + AbstractBuilder::BTN_FULL_WIDTH)
                                        ->setId($ids['btnExecId'])->addText($this->trans->lang('Execute'))
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
        return $this->htmlBuilder->build();
    }
}
