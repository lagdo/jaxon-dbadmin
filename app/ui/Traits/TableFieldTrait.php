<?php

namespace Lagdo\DbAdmin\Ui\Traits;

use Lagdo\DbAdmin\Ajax\App\Db\Table\Ddl\Columns;
use Lagdo\DbAdmin\Driver\Entity\TableFieldEntity;
use Lagdo\UiBuilder\BuilderInterface;

use function is_array;
use function is_string;
use function Jaxon\rq;
use function strcasecmp;

trait TableFieldTrait
{
    /**
     * @var array
     */
    protected $table = [];

    /**
     * @var array
     */
    protected $support = [];

    /**
     * @var array
     */
    protected $engines = [];

    /**
     * @var array
     */
    protected $collations = [];

    /**
     * @var array
     */
    protected $unsigned = [];

    /**
     * @var array
     */
    protected $foreignKeys = [];

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var array
     */
    protected $fields = [];

    /**
     * @var array
     */
    protected $handlers = [];

    /**
     * @var string
     */
    protected $fieldPrefix;

    /**
     * @var int
     */
    protected $fieldIndex;

    /**
     * @return BuilderInterface
     */
    abstract protected function builder(): BuilderInterface;

    /**
     * @param string $currentEngine
     *
     * @return mixed
     */
    protected function getEngineSelect(string $currentEngine): mixed
    {
        $html = $this->builder();
        return $html->formSelect(
            $html->option('(engine)')
                ->selected(false)->setValue(''),
            $html->each($this->engines, fn($engine) =>
                $html->option($engine)
                    ->selected(!strcasecmp($currentEngine, $engine))
            )
        );
    }

    /**
     * @param string $currentCollation
     *
     * @return mixed
     */
    protected function getCollationSelect(string $currentCollation): mixed
    {
        $html = $this->builder();
        return $html->formSelect(
            $html->option('(' . $this->trans->lang('collation') . ')')
                ->selected(false)->setValue(''),
            $html->each($this->collations, fn($_collations, $group) =>
                $html->list(
                    $html->when(is_string($_collations), fn() =>
                        $html->option($_collations)
                            ->selected($currentCollation === $_collations)
                    ),
                    $html->when(is_array($_collations), fn() =>
                        $html->optgroup(
                            $html->each($_collations, fn($collation) =>
                                $html->option($collation)
                                    ->selected($currentCollation === $collation)
                            )
                        )
                        ->setLabel($group)
                    )
                )
            )
        );
    }

    /**
     * @param TableFieldEntity $field
     *
     * @return mixed
     */
    protected function getFieldNameCol(TableFieldEntity $field): mixed
    {
        $html = $this->builder();
        return $html->col(
            $html->formInput(['class' => 'column-name'])
                ->setName($this->fieldPrefix . '[name]')
                ->setPlaceholder($this->trans->lang('Name'))
                ->setValue($field->name ?? '')
                ->setDataField('name')
                ->setDataMaxlength('64')
                ->setAutocapitalize('off'),
            $html->input()
                ->setType('hidden')
                ->setName($this->fieldPrefix . '[orig]')
                ->setValue($field->name ?? '')
                ->setDataField('orig')
        );
    }

    /**
     * @param TableFieldEntity $field
     *
     * @return mixed
     */
    protected function getAutoIncrementCol(TableFieldEntity $field): mixed
    {
        $html = $this->builder();
        return $html->col(
            $html->radio($field->autoIncrement)
                ->setName('autoIncrementCol')
                ->setValue($this->fieldIndex + 1),
            $html->span()
                ->addHtml('&nbsp;AI&nbsp;'),
            $html->checkbox($field->primary)
                ->setName($this->fieldPrefix . '[primary]')
        );
    }

    /**
     * @param TableFieldEntity $field
     *
     * @return mixed
     */
    protected function getCollectionCol(TableFieldEntity $field): mixed
    {
        $html = $this->builder();
        return $html->col(
            $this->getCollationSelect($field->collation)
                ->setName($this->fieldPrefix . '[collation]')->setDataField('collation')
                ->when($field->collationHidden, fn($elt) => $elt->setReadonly('readonly'))
        );
    }

    /**
     * @param TableFieldEntity $field
     *
     * @return mixed
     */
    protected function getOnUpdateCol(TableFieldEntity $field): mixed
    {
        $html = $this->builder();
        return $html->col(
            $html->formSelect(
                $html->option('(' . $this->trans->lang('ON UPDATE') . ')')
                    ->selected(false)->setValue(''),
                $html->each($this->options['onUpdate'], fn($option) =>
                    $html->option($option)
                        ->selected($field->onUpdate === $option)
                )
            )
            ->setName($this->fieldPrefix . '[onUpdate]')
            ->setDataField('onUpdate')
            ->when($field->onUpdateHidden, fn($elt) => $elt->setReadonly('readonly'))
        );
    }

    /**
     * @param TableFieldEntity $field
     *
     * @return mixed
     */
    protected function getCommentCol(TableFieldEntity $field): mixed
    {
        $html = $this->builder();
        return $html->col(
            $html->when(/*$support['comment']*/true, fn() =>
                $html->formInput()
                    ->setType('text')
                    ->setName($this->fieldPrefix . '[comment]')
                    ->setValue($field->comment ?? '')
                    ->setDataField('comment')
                    ->setPlaceholder($this->trans->lang('Comment'))
            )
        );
    }

    /**
     * @param TableFieldEntity $field
     *
     * @return mixed
     */
    protected function getTypeCol(TableFieldEntity $field): mixed
    {
        $html = $this->builder();
        return $html->col(
            $html->formSelect(
                $html->each($field->types, fn($_types, $group) =>
                    $html->optgroup(
                        $html->each($_types, fn($type) =>
                            $html->option($type)
                                ->selected($field->type === $type)
                        )
                    )
                    ->setLabel($group)
                )
            )
            ->setName($this->fieldPrefix . '[type]')
            ->setDataField('type')
        );
    }

    /**
     * @param TableFieldEntity $field
     *
     * @return mixed
     */
    protected function getLengthCol(TableFieldEntity $field): mixed
    {
        $html = $this->builder();
        return $html->col(
            $html->formInput()
                ->setName($this->fieldPrefix . '[length]')
                ->setDataField('length')
                ->setSize('3')
                ->setPlaceholder($this->trans->lang('Length'))
                ->setValue($field->length)
                ->when($field->lengthRequired, fn($elt) => $elt->setRequired('required'))
        );
    }

    /**
     * @param TableFieldEntity $field
     *
     * @return mixed
     */
    protected function getNullableCol(TableFieldEntity $field): mixed
    {
        $html = $this->builder();
        return $html->col(
            $html->checkbox()
                ->selected($field->null)
                ->setName($this->fieldPrefix . '[null]')
                ->setDataField('null')
                ->setValue('1'),
            $html->span()
                ->addHtml('&nbsp;Null')
        );
    }

    /**
     * @param TableFieldEntity $field
     *
     * @return mixed
     */
    protected function getUnsignedCol(TableFieldEntity $field): mixed
    {
        $html = $this->builder();
        return $html->col(
            $html->formSelect(
                $html->option('')->selected(false),
                $html->each($this->unsigned, fn($option) =>
                    $html->option($option)
                        ->selected($field->unsigned === $option)
                )
            )
            ->setName($this->fieldPrefix . '[unsigned]')
            ->setDataField('unsigned')
            ->when($field->unsignedHidden, fn($elt) => $elt->setReadonly('readonly'))
        );
    }

    /**
     * @param TableFieldEntity $field
     *
     * @return mixed
     */
    protected function getOnDeleteCol(TableFieldEntity $field): mixed
    {
        $html = $this->builder();
        return $html->col(
            $html->formSelect(
                $html->option('(' . $this->trans->lang('ON DELETE') . ')')
                    ->selected(false)->setValue(''),
                $html->each($this->options['onDelete'], fn($option) =>
                    $html->option($option)
                        ->selected($field->onDelete === $option)
                )
            )
            ->setName($this->fieldPrefix . '[onDelete]')
            ->setDataField('onDelete')
            ->when($field->onDeleteHidden, fn($elt) => $elt->setReadonly('readonly'))
        );
    }

    /**
     * @param TableFieldEntity $field
     *
     * @return mixed
     */
    protected function getDefaultCol(TableFieldEntity $field): mixed
    {
        $html = $this->builder();
        return $html->col(
            $html->inputGroup(
                $html->checkbox()
                    ->checked($field->hasDefault)
                    ->setName($this->fieldPrefix . '[hasDefault]')
                    ->setDataField('hasDefault'),
                $html->formInput()
                    ->setName($this->fieldPrefix . '[default]')
                    ->setDataField('default')
                    ->setPlaceholder($this->trans->lang('Default value'))
                    ->setValue($field->default ?? '')
            )
        );
    }

    /**
     * @param TableFieldEntity $field
     *
     * @return mixed
     */
    protected function getActionCol(TableFieldEntity $field): mixed
    {
        $html = $this->builder();
        return $html->col(
            // $html->buttonGroup(
            //     $html->when($this->support['move_col'], fn() =>
            //         $html->button()
            //             ->primary()->setDataIndex($this->fieldIndex)->addIcon('plus')
            //             ->setClass('dbadmin-table-column-add')
            //     ),
            //     $html->when($this->support['drop_col'], fn() =>
            //         $html->button()
            //             ->primary()->setDataIndex($this->fieldIndex)->addIcon('remove')
            //             ->setClass('dbadmin-table-column-del')
            //     )
            // )
            // ->fullWidth(false),
            $html->dropdown(
                $html->dropdownItem()
                    ->style('primary')->addCaret(),
                $html->dropdownMenu(
                    $html->when($this->support['move_col'], fn() =>
                        $html->dropdownMenuItem()
                            ->jxnClick(rq(Columns::class)->add($this->fieldIndex))
                            ->addIcon('plus')
                    ),
                    $html->when($this->support['drop_col'], fn() =>
                        $html->dropdownMenuItem()
                            ->jxnClick(rq(Columns::class)->setForDelete($this->fieldIndex))
                            ->addIcon('remove')
                    )
                )
            )
            ->setClass('dbadmin-table-column-buttons')
        );
    }
}
