<?php

namespace Lagdo\DbAdmin\Ui\Traits;

use Lagdo\DbAdmin\Driver\Entity\TableFieldEntity;
use Lagdo\UiBuilder\BuilderInterface;
use Lagdo\UiBuilder\Jaxon\Builder;

use function strcasecmp;
use function is_string;
use function sprintf;

trait TableTrait
{
    /**
     * @param string $formId
     * @param array $support
     * @param array $engines
     * @param array $collations
     * @param array $unsigned
     * @param array $foreignKeys
     * @param array $options
     * @param array $table
     * @param array $fields
     *
     * @return string
     */
    public function tableForm(string $formId, array $support, array $engines, array $collations, array $unsigned = [],
                              array $foreignKeys = [], array $options = [], array $table = [], array $fields = []): string
    {
        $htmlBuilder = Builder::new();
        $htmlBuilder
            ->form(true, false)->setId($formId)
                ->formRow()->setClass('adminer-edit-table-header')
                    ->formCol(2)
                        ->label()->addText('Table')
                        ->end()
                    ->end()
                ->end()
                ->formRow()->setClass('adminer-edit-table-header')
                    ->formCol(3)->setClass('adminer-edit-table-name')
                        ->formInput()->setType('text')->setName('name')
                            ->setValue($table['name'] ?? '')->setPlaceholder('Name')
                        ->end()
                    ->end();
        if (($engines)) {
            $currentEngine = $table['engine'] ?? '';
            $htmlBuilder
                    ->formCol(2)->setClass('adminer-edit-table-engine')
                        ->formSelect()->setName('engine')
                            ->option(false, '(engine)')->setValue('')
                            ->end();
            foreach ($engines as $engine) {
                $htmlBuilder
                            ->option(!strcasecmp($currentEngine, $engine), $engine)
                            ->end();
            }
            $htmlBuilder
                        ->end()
                    ->end();
        }
        if (($collations)) {
            $currentCollation = $table['collation'] ?? '';
            $htmlBuilder
                    ->formCol(3)->setClass('adminer-edit-table-collation')
                        ->formSelect()->setName('collation')
                            ->option(false, '(' . $this->trans->lang('collation') . ')')->setValue('')
                            ->end();
            foreach ($collations as $group => $_collations) {
                if (is_string($_collations)) {
                    $htmlBuilder
                            ->option($currentCollation === $_collations, $_collations)
                            ->end();
                    continue;
                }
                $htmlBuilder
                            ->optgroup()->setLabel($group);
                foreach ($_collations as $collation) {
                    $htmlBuilder
                                ->option($currentCollation === $collation, $collation)
                                ->end();
                }
                $htmlBuilder
                            ->end();
            }
            $htmlBuilder
                        ->end()
                    ->end();
        }
        if ($support['comment']) {
            $htmlBuilder
                    ->formCol(4)->setClass('adminer-table-column-middle')
                        ->formInput()->setType('text')->setName('comment')
                            ->setValue($table['comment'] ?? '')->setPlaceholder($this->trans->lang('Comment'))
                        ->end()
                    ->end();
        }
        $htmlBuilder
                ->end()
                ->formRow()->setClass('adminer-table-column-header')
                    ->formCol(3)->setClass('adminer-table-column-left')
                        ->label()->addText($this->trans->lang('Column'))
                        ->end()
                    ->end()
                    ->formCol(1)->setClass('adminer-table-column-null-header')
                        ->radio(true)->setName('autoIncrementCol')->setValue('0')
                        ->end()
                        ->label()->addHtml('&nbsp;AI-P')
                        ->end()
                    ->end()
                    ->formCol(7)->setClass('adminer-table-column-middle')
                        ->label()->addText($this->trans->lang('Options'))
                        ->end()
                    ->end()
                    ->formCol(1)->setClass('adminer-table-column-buttons-header');
        if ($support['columns']) {
            $htmlBuilder
                        ->button()->btnPrimary()
                            ->setId('adminer-table-column-add')->addIcon('plus')
                        ->end();
        }
        $htmlBuilder
                    ->end()
                ->end();
        $index = 0;
        foreach ($fields as $field) {
            $this->_tableColumn($formId . '-column', $index, $field, sprintf("fields[%d]", ++$index),
                $support, $collations, $unsigned, $options);
            $index++;
        }
        $htmlBuilder
            ->end();
        return $htmlBuilder->build();
    }

    /**
     * @param BuilderInterface $htmlBuilder
     * @param string $class
     * @param int $index
     * @param TableFieldEntity $field
     * @param string $prefixFields
     * @param array $support
     * @param array $collations
     * @param array $unsigned
     * @param array $options
     * @param bool $wrap
     *
     * @return void
     */
    private function _tableColumn(BuilderInterface $htmlBuilder, string $class, int $index,
        TableFieldEntity $field, string $prefixFields, array $support, array $collations,
        array $unsigned, array $options, bool $wrap = true)
    {
        if ($wrap) {
            $htmlBuilder->formRow()->setClass($class)
                ->setDataIndex($index)->setId(sprintf('%s-%02d', $class, $index));
        }
        $htmlBuilder
            ->col(12)
                ->row()
                    ->col(3)->setClass('adminer-table-column-left')
                        ->formInput(['class' => 'column-name'])->setName($prefixFields . '[name]')
                            ->setPlaceholder($this->trans->lang('Name'))->setValue($field->name)
                            ->setDataField('name')->setDataMaxlength('64')->setAutocapitalize('off')
                        ->endShorted()
                        ->input()->setType('hidden')->setName($prefixFields . '[orig]')
                            ->setValue($field->name)->setDataField('orig')
                        ->endShorted()
                    ->end()
                    ->col(1)->setClass('adminer-table-column-null')
                        ->radio($field->autoIncrement)->setName('autoIncrementCol')->setValue($index + 1)
                        ->endShorted()
                        ->addHtml('&nbsp;AI&nbsp;')
                        ->checkbox($field->primary)->setName($prefixFields . '[primary]')
                        ->endShorted()
                    ->end()
                    ->col(2)->setClass('adminer-table-column-middle')
                        ->formSelect()->setName($prefixFields . '[collation]')->setDataField('collation');
        if ($field->collationHidden) {
            $htmlBuilder
                            ->setReadonly('readonly');
        }
        $htmlBuilder
                            ->option(false, '(' . $this->trans->lang('collation') . ')')->setValue('')
                            ->end();
        foreach ($collations as $group => $_collations) {
            if (is_string($_collations)) {
                $htmlBuilder
                            ->option($field->collation === $_collations, $_collations)
                            ->end();
                continue;
            }
            $htmlBuilder
                            ->optgroup()->setLabel($group);
            foreach ($_collations as $collation) {
                $htmlBuilder
                                ->option($field->collation === $collation, $collation)
                                ->end();
            }
            $htmlBuilder
                            ->end();
        }
        $htmlBuilder
                        ->end()
                    ->end()
                    ->col(2)->setClass('adminer-table-column-middle')
                        ->formSelect()->setName($prefixFields . '[onUpdate]')->setDataField('onUpdate');
        if ($field->onUpdateHidden) {
            $htmlBuilder
                                ->setReadonly('readonly');
        }
        $htmlBuilder
                            ->option(false, '(' . $this->trans->lang('ON UPDATE') . ')')->setValue('')
                            ->end();
        foreach ($options['onUpdate'] as $option) {
            $htmlBuilder
                            ->option($field->onUpdate === $option, $option)
                            ->end();
        }
        $htmlBuilder
                        ->end()
                    ->end()
                    ->col(4)->setClass('adminer-table-column-right');
        if (/*$support['comment']*/true) {
            $htmlBuilder
                        ->formInput()->setType('text')->setName($prefixFields . '[comment]')->setValue($field->comment)
                            ->setDataField('comment')->setPlaceholder($this->trans->lang('Comment'))
                        ->end();
        }
        $htmlBuilder
                    ->end()
                    ->col(2)->setClass('adminer-table-column-left second-line')
                        ->formSelect()->setName($prefixFields . '[type]')->setDataField('type');
        foreach ($field->types as $group => $_types) {
            $htmlBuilder
                            ->optgroup()->setLabel($group);
            foreach ($_types as $type) {
                $htmlBuilder
                                ->option($field->type === $type, $type)
                                ->end();
            }
            $htmlBuilder
                            ->end();
        }
        $htmlBuilder
                        ->end()
                    ->end()
                    ->col(1)->setClass('adminer-table-column-middle second-line')
                        ->formInput()->setName($prefixFields . '[length]')->setDataField('length')->setSize('3')
                            ->setPlaceholder($this->trans->lang('Length'))->setValue($field->length);
        if ($field->lengthRequired) {
            $htmlBuilder
                            ->setRequired('required');
        }
        $htmlBuilder
                        ->endShorted()
                    ->end()
                    ->col(1)->setClass('adminer-table-column-null second-line')
                        ->checkbox($field->null)->setName($prefixFields . '[null]')->setDataField('null')->setValue('1')
                        ->endShorted()
                        ->addHtml('&nbsp;Null')
                    ->end()
                    ->col(2)->setClass('adminer-table-column-middle second-line')
                        ->formSelect()->setName($prefixFields . '[unsigned]')->setDataField('unsigned');
        if ($field->unsignedHidden) {
            $htmlBuilder
                            ->setReadonly('readonly');
        }
        $htmlBuilder
                            ->option(false, '')
                            ->end();
        foreach ($unsigned as $option) {
            $htmlBuilder
                            ->option($field->unsigned === $option, $option)
                            ->end();
        }
        $htmlBuilder
                        ->end()
                    ->end()
                    ->col(2)->setClass('adminer-table-column-middle second-line')
                        ->formSelect()->setName($prefixFields . '[onDelete]')->setDataField('onDelete');
        if ($field->onDeleteHidden) {
            $htmlBuilder
                                ->setReadonly('readonly');
        }
        $htmlBuilder
                            ->option(false, '(' . $this->trans->lang('ON DELETE') . ')')->setValue('')
                            ->end();
        foreach ($options['onDelete'] as $option) {
            $htmlBuilder
                            ->option($field->onDelete === $option, $option)
                            ->end();
        }
        $htmlBuilder
                        ->end()
                    ->end()
                    ->col(3)->setClass('adminer-table-column-middle second-line')
                        ->inputGroup()
                            ->checkbox($field->hasDefault)->setName($prefixFields . '[hasDefault]')->setDataField('hasDefault')
                            ->end()
                            ->formInput()->setName($prefixFields . '[default]')->setDataField('default')
                                ->setPlaceholder($this->trans->lang('Default value'))->setValue($field->default ?? '')
                            ->end()
                        ->end()
                    ->end()
                    ->col(1)->setClass('adminer-table-column-buttons second-line')
                        /*->buttonGroup(false);
        if ($support['move_col']) {
            $htmlBuilder
                            ->button()->btnPrimary()
                                ->setClass('adminer-table-column-add')->setDataIndex($index)->addIcon('plus')
                            ->end();
        }
        if ($support['drop_col']) {
            $htmlBuilder
                            ->button()->btnPrimary()
                                ->setClass('adminer-table-column-del')->setDataIndex($index)->addIcon('remove')
                            ->end();
        }
        $htmlBuilder
                        ->end()*/
                        ->dropdown()->setClass('adminer-table-column-buttons')
                            ->dropdownItem('primary')->setDiv("adminer-table-column-button-group-drop-$index")->addCaret()
                            ->end()
                            ->dropdownMenu();
        if ($support['move_col']) {
            $htmlBuilder
                                ->dropdownMenuItem()
                                    ->setClass('adminer-table-column-add')->setDataIndex($index)->addIcon('plus')
                                ->end();
        }
        if ($support['drop_col']) {
            $htmlBuilder
                                ->dropdownMenuItem()
                                    ->setClass('adminer-table-column-del')->setDataIndex($index)->addIcon('remove')
                                ->end();
        }
        $htmlBuilder
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
        if ($wrap) {
            $htmlBuilder->end();
        }
    }

    /**
     * @param string $class
     * @param int $index
     * @param TableFieldEntity $field
     * @param string $prefixFields
     * @param array $support
     * @param array $collations
     * @param array $unsigned
     * @param array $options
     * @param bool $wrap
     *
     * @return string
     */
    public function tableColumn(string $class, int $index, TableFieldEntity $field,
        string $prefixFields, array $support, array $collations, array $unsigned,
        array $options, bool $wrap): string
    {
        $htmlBuilder = Builder::new();
        $htmlBuilder;
        $this->_tableColumn($htmlBuilder, $class, $index, $field, $prefixFields,
            $support, $collations, $unsigned, $options, $wrap);
        return $htmlBuilder->build();
    }

    /**
     * @param string $formId
     * @param array $fields
     *
     * @return string
     */
    public function tableQueryForm(string $formId, array $fields): string
    {
        $htmlBuilder = Builder::new();
        $htmlBuilder
            ->form(true, false)->setId($formId);
        foreach ($fields as $name => $field) {
            $htmlBuilder
                ->formRow()
                    ->formCol(3)
                        ->label($field['name'])->setTitle($field['type'])
                        ->end()
                    ->end()
                    ->formCol(2);
            if($field['functions']['type'] === 'name') {
                $htmlBuilder
                        ->label($field['functions']['name'])
                        ->end();
            } elseif($field['functions']['type'] === 'select') {
                $htmlBuilder
                        ->formSelect()->setName($field['functions']['name']);
                foreach($field['functions']['options'] as $function) {
                    $htmlBuilder
                            ->option($function === $field['functions']['selected'], $function)
                            ->end();
                }
                $htmlBuilder
                        ->end();
            }
            $htmlBuilder
                    ->end()
                    ->formCol(7);
            $this->inputBuilder->build($field['input']['type'], $field['input']);
            $htmlBuilder
                    ->end()
                ->end();
        }
        $htmlBuilder
            ->end();
        return $htmlBuilder->build();
    }
}
