<?php

namespace Lagdo\DbAdmin\Ui\Traits;

use Lagdo\DbAdmin\Ajax\App\Db\Table\Ddl\Columns;
use Lagdo\DbAdmin\Driver\Entity\TableFieldEntity;
use Lagdo\UiBuilder\BuilderInterface;

use function count;
use function is_array;
use function is_string;
use function Jaxon\je;
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
    protected $formId = '';

    /**
     * @var string
     */
    protected $editPrefix = '';

    /**
     * @return BuilderInterface
     */
    abstract protected function builder(): BuilderInterface;

    /**
     * @return array
     */
    protected function formValues(): array
    {
        return je($this->formId)->rd()->form();
    }

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
                ->setName($this->editPrefix . '[name]')
                ->setPlaceholder($this->trans->lang('Name'))
                ->setValue($field->name ?? '')
                ->setDataField('name')
                ->setDataMaxlength('64')
                ->setAutocapitalize('off')
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
                ->setValue($field->editPosition + 1),
            $html->span($html->html('&nbsp;AI&nbsp;')),
            $html->checkbox($field->primary)
                ->setName($this->editPrefix . '[primary]')
        );
    }

    /**
     * @param TableFieldEntity $field
     *
     * @return mixed
     */
    protected function getCollationCol(TableFieldEntity $field): mixed
    {
        $html = $this->builder();
        return $html->col(
            $this->getCollationSelect($field->collation)
                ->setName($this->editPrefix . '[collation]')->setDataField('collation')
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
                    ->setValue('')->selected(false),
                $html->each($this->options['onUpdate'], fn($option, $value) =>
                    $html->option($option)
                        ->selected($field->onUpdate === $option)
                        ->setValue($value)
                )
            )
            ->setName($this->editPrefix . '[onUpdate]')
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
                    ->setName($this->editPrefix . '[comment]')
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
            ->setName($this->editPrefix . '[type]')
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
                ->setStyle('width: 100%')
                ->setName($this->editPrefix . '[length]')
                ->setDataField('length')
                ->setSize('3')
                ->setPlaceholder($this->trans->lang('Length'))
                ->setValue($field->length ?: '')
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
                ->checked($field->null)
                ->setName($this->editPrefix . '[null]')
                ->setDataField('null')
                ->setValue('1'),
            $html->span($html->html('&nbsp;Null'))
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
                $html->option('(unsigned)')
                    ->selected(false)
                    ->setValue(''),
                $html->each($this->unsigned, fn($option) =>
                    $html->option($option)
                        ->selected($field->unsigned === $option)
                        ->setValue($option)
                )
            )
            ->setName($this->editPrefix . '[unsigned]')
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
                    ->setValue('')
                    ->selected(false),
                $html->each($this->options['onDelete'], fn($option) =>
                    $html->option($option)
                        ->setValue($option)
                        ->selected($field->onDelete === $option)
                )
            )
            ->setName($this->editPrefix . '[onDelete]')
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
                    ->setName($this->editPrefix . '[hasDefault]')
                    ->setDataField('hasDefault'),
                $html->formInput()
                    ->setName($this->editPrefix . '[default]')
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
        $notFirst = $field->editPosition > 0;
        $notLast = $field->editPosition < count($this->fields) - 1;
        $deleted = $field->editStatus === 'deleted';
        $parameters = [$this->formValues(), $field->editPosition];

        $html = $this->builder();
        return $html->col(
            $html->dropdown(
                $html->dropdownItem()
                    ->style('primary')->addCaret(),
                $html->dropdownMenu(
                    $html->when($notFirst && $this->support['move_col'], fn() =>
                        $html->dropdownMenuItem($this->html->text('Up'))
                            ->jxnClick(rq(Columns::class)->up(...$parameters))
                    ),
                    $html->when($notLast && $this->support['move_col'], fn() =>
                        $html->dropdownMenuItem($this->html->text('Down'))
                            ->jxnClick(rq(Columns::class)->down(...$parameters))
                    ),
                    $html->when($this->support['move_col'], fn() =>
                        $html->dropdownMenuItem($this->html->text('Add'))
                            ->jxnClick(rq(Columns::class)->add(...$parameters))
                    ),
                    $html->when($this->support['drop_col'] && !$deleted, fn() =>
                        $html->dropdownMenuItem($this->html->text('Remove'))
                            ->jxnClick(rq(Columns::class)->del(...$parameters))
                    ),
                    $html->when($this->support['drop_col'] && $deleted, fn() =>
                        $html->dropdownMenuItem($this->html->text('Cancel'))
                            ->jxnClick(rq(Columns::class)->cancel(...$parameters))
                    )
                )
            )
            ->setClass('dbadmin-table-column-buttons')
        );
    }
}
