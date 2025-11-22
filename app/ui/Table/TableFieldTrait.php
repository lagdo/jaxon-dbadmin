<?php

namespace Lagdo\DbAdmin\Ui\Table;

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
     * @var BuilderInterface
     */
    protected BuilderInterface $ui;

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
        return $this->ui->formSelect(
            $this->ui->option('(engine)')
                ->selected(false)->setValue(''),
            $this->ui->each($this->engines, fn($engine) =>
                $this->ui->option($engine)
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
        return $this->ui->formSelect(
            $this->ui->option('(' . $this->trans->lang('collation') . ')')
                ->selected(false)->setValue(''),
            $this->ui->each($this->collations, fn($_collations, $group) =>
                $this->ui->list(
                    $this->ui->when(is_string($_collations), fn() =>
                        $this->ui->option($_collations)
                            ->selected($currentCollation === $_collations)
                    ),
                    $this->ui->when(is_array($_collations), fn() =>
                        $this->ui->optgroup(
                            $this->ui->each($_collations, fn($collation) =>
                                $this->ui->option($collation)
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
        return $this->ui->col(
            $this->ui->formInput(['class' => 'column-name'])
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
        return $this->ui->col(
            $this->ui->radio($field->autoIncrement)
                ->setName('autoIncrementCol')
                ->setValue($field->editPosition + 1),
            $this->ui->span($this->ui->html('&nbsp;AI&nbsp;')),
            $this->ui->checkbox($field->primary)
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
        return $this->ui->col(
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
        return $this->ui->col(
            $this->ui->formSelect(
                $this->ui->option('(' . $this->trans->lang('ON UPDATE') . ')')
                    ->setValue('')->selected(false),
                $this->ui->each($this->options['onUpdate'], fn($option, $value) =>
                    $this->ui->option($option)
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
        return $this->ui->col(
            $this->ui->when(/*$support['comment']*/true, fn() =>
                $this->ui->formInput()
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
        return $this->ui->col(
            $this->ui->formSelect(
                $this->ui->each($field->types, fn($_types, $group) =>
                    $this->ui->optgroup(
                        $this->ui->each($_types, fn($type) =>
                            $this->ui->option($type)
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
        return $this->ui->col(
            $this->ui->formInput()
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
        return $this->ui->col(
            $this->ui->checkbox()
                ->checked($field->null)
                ->setName($this->editPrefix . '[null]')
                ->setDataField('null')
                ->setValue('1'),
            $this->ui->span($this->ui->html('&nbsp;Null'))
        );
    }

    /**
     * @param TableFieldEntity $field
     *
     * @return mixed
     */
    protected function getUnsignedCol(TableFieldEntity $field): mixed
    {
        return $this->ui->col(
            $this->ui->formSelect(
                $this->ui->option('(unsigned)')
                    ->selected(false)
                    ->setValue(''),
                $this->ui->each($this->unsigned, fn($option) =>
                    $this->ui->option($option)
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
        return $this->ui->col(
            $this->ui->formSelect(
                $this->ui->option('(' . $this->trans->lang('ON DELETE') . ')')
                    ->setValue('')
                    ->selected(false),
                $this->ui->each($this->options['onDelete'], fn($option) =>
                    $this->ui->option($option)
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
        return $this->ui->col(
            $this->ui->inputGroup(
                $this->ui->checkbox()
                    ->checked($field->hasDefault())
                    ->setName($this->editPrefix . '[hasDefault]')
                    ->setDataField('hasDefault'),
                $this->ui->formInput()
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

        return $this->ui->col(
            $this->ui->dropdown(
                $this->ui->dropdownItem()
                    ->style('primary')->addCaret(),
                $this->ui->dropdownMenu(
                    $this->ui->when($notFirst && $this->support['move_col'], fn() =>
                        $this->ui->dropdownMenuItem($this->ui->text('Up'))
                            ->jxnClick(rq(Columns::class)->up(...$parameters))
                    ),
                    $this->ui->when($notLast && $this->support['move_col'], fn() =>
                        $this->ui->dropdownMenuItem($this->ui->text('Down'))
                            ->jxnClick(rq(Columns::class)->down(...$parameters))
                    ),
                    $this->ui->when($this->support['move_col'], fn() =>
                        $this->ui->dropdownMenuItem($this->ui->text('Add'))
                            ->jxnClick(rq(Columns::class)->add(...$parameters))
                    ),
                    $this->ui->when($this->support['drop_col'] && !$deleted, fn() =>
                        $this->ui->dropdownMenuItem($this->ui->text('Remove'))
                            ->jxnClick(rq(Columns::class)->del(...$parameters))
                    ),
                    $this->ui->when($this->support['drop_col'] && $deleted, fn() =>
                        $this->ui->dropdownMenuItem($this->ui->text('Cancel'))
                            ->jxnClick(rq(Columns::class)->cancel(...$parameters))
                    )
                )
            )
            ->setClass('dbadmin-table-column-buttons')
        );
    }
}
