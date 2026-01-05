<?php

namespace Lagdo\DbAdmin\Ui\Table;

use Lagdo\DbAdmin\Db\Page\Ddl\ColumnEntity;
use Lagdo\UiBuilder\BuilderInterface;
use Lagdo\UiBuilder\Component\HtmlComponent;

use function is_array;
use function is_string;
use function Jaxon\je;
use function strcasecmp;

trait TableFieldTrait
{
    use FieldMetadataTrait;

    /**
     * @var array
     */
    protected $table = [];

    /**
     * @var array
     */
    protected $columns = [];

    /**
     * @var string
     */
    protected $formId = '';

    /**
     * @var bool
     */
    protected $listMode = true;

    /**
     * @var BuilderInterface
     */
    protected BuilderInterface $ui;

    /**
     * @param string $formId
     *
     * @return self
     */
    public function formId(string $formId): self
    {
        $this->formId = $formId;
        return $this;
    }

    /**
     * @return array
     */
    protected function formValues(): array
    {
        return je($this->formId)->rd()->form();
    }

    /**
     * @param HtmlComponent $component
     * @param bool $changeBackground
     *
     * @return void
     */
    protected function disable(HtmlComponent $component, bool $changeBackground = true): void
    {
        $component->setDisabled('disabled');
        if ($changeBackground) {
            $component->setStyle('background-color: #f8f8f8;');
        }
    }

    /**
     * @param string $currentEngine
     *
     * @return mixed
     */
    protected function getEngineSelect(string $currentEngine): mixed
    {
        return $this->ui->select(
            $this->ui->option('(engine)')
                ->selected(false)
                ->setValue(''),
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
        return $this->ui->select(
            $this->ui->option('(' . $this->trans->lang('collation') . ')')
                ->selected(false)
                ->setValue(''),
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
                        )->setLabel($group)
                    )
                )
            )
        );
    }

    /**
     * @param ColumnEntity $column
     * @param string $fieldName
     *
     * @return mixed
     */
    protected function getColumnNameField(ColumnEntity $column, string $fieldName): mixed
    {
        return $this->ui->input(['class' => 'column-name'])
            ->setName($fieldName)
            ->setValue($column->values()->name)
            ->setDataField('name')
            ->setDataMaxlength('64')
            ->setAutocapitalize('off');
    }

    /**
     * @param ColumnEntity $column
     * @param string $fieldName
     *
     * @return mixed
     */
    protected function getColumnPrimaryField(ColumnEntity $column, string $fieldName): mixed
    {
        return $this->ui->checkbox()
            ->checked($column->values()->primary)
            ->setName($fieldName)
            ->setValue('1');
    }

    /**
     * @param ColumnEntity $column
     * @param string $fieldName
     *
     * @return mixed
     */
    protected function getColumnAutoIncrementField(ColumnEntity $column, string $fieldName): mixed
    {
        return $this->ui->checkbox()
            ->checked($column->values()->autoIncrement)
            ->setName($fieldName)
            ->setValue('1');
    }

    /**
     * @param ColumnEntity $column
     * @param string $fieldName
     *
     * @return mixed
     */
    protected function getColumnCollationField(ColumnEntity $column, string $fieldName): mixed
    {
        return $this->getCollationSelect($column->values()->collation)
            ->setName($fieldName)
            ->setDataField('collation')
            ->when($column->field()->collationHidden, fn($input) => $input->setReadonly('readonly'));
    }

    /**
     * @param ColumnEntity $column
     * @param string $fieldName
     *
     * @return mixed
     */
    protected function getColumnOnUpdateField(ColumnEntity $column, string $fieldName): mixed
    {
        return $this->ui->select(
            $this->ui->option('(' . $this->trans->lang('ON UPDATE') . ')')
                ->setValue('')->selected(false),
            $this->ui->each($this->options['onUpdate'], fn($option, $value) =>
                $this->ui->option($option)
                    ->selected($column->values()->onUpdate === $option)
                    ->setValue($value)
            )
        )->setName($fieldName)
            ->setDataField('onUpdate')
            ->when($column->field()->onUpdateHidden, fn($input) => $input->setReadonly('readonly'));
    }

    /**
     * @param ColumnEntity $column
     * @param string $fieldName
     *
     * @return mixed
     */
    protected function getColumnCommentField(ColumnEntity $column, string $fieldName): mixed
    {
        // return $this->ui->when(/*$support['comment']*/true, fn() =>
        //     $this->ui->input()
        //         ->setType('text')
        //         ->setName($fieldName)
        //         ->setValue($column->values()->comment ?? '')
        //         ->setDataField('comment')
        // );
        return $this->ui->input()
            ->setType('text')
            ->setName($fieldName)
            ->setValue($column->values()->comment ?? '')
            ->setDataField('comment');
    }

    /**
     * @param ColumnEntity $column
     * @param string $fieldName
     *
     * @return mixed
     */
    protected function getColumnTypeField(ColumnEntity $column, string $fieldName): mixed
    {
        return $this->ui->select(
            $this->ui->each($column->field()->types, fn($groupTypes, $groupName) =>
                is_numeric($groupName) ?
                    $this->ui->each($groupTypes, fn($type, $key) =>
                        $this->ui->option($type)
                            ->selected($column->values()->type === $type)
                            ->when(!is_numeric($key), fn($input) => $input->setValue($key))) :
                    $this->ui->optgroup(
                        $this->ui->each($groupTypes, fn($type, $key) =>
                            $this->ui->option($type)
                                ->selected($column->values()->type === $type)
                                ->when(!is_numeric($key), fn($input) => $input->setValue($key))
                        )
                    )->setLabel($groupName)
            )
        )->setName($fieldName)
            ->setDataField('type');
    }

    /**
     * @param ColumnEntity $column
     * @param string $fieldName
     *
     * @return mixed
     */
    protected function getColumnLengthField(ColumnEntity $column, string $fieldName): mixed
    {
        return $this->ui->input()
            ->setStyle('width: 100%')
            ->setName($fieldName)
            ->setPlaceholder($this->trans->lang('Length'))
            ->setDataField('length')
            ->setSize('3')
            ->setValue($column->values()->length ?: '')
            ->when($column->field()->lengthRequired, fn($input) => $input->setRequired('required'));
    }

    /**
     * @param ColumnEntity $column
     * @param string $fieldName
     *
     * @return mixed
     */
    protected function getColumnNullableField(ColumnEntity $column, string $fieldName): mixed
    {
        return $this->ui->checkbox()
            ->checked($column->values()->nullable)
            ->setName($fieldName)
            ->setDataField('null')
            ->setValue('1');
    }

    /**
     * @param ColumnEntity $column
     * @param string $fieldName
     *
     * @return mixed
     */
    protected function getColumnUnsignedField(ColumnEntity $column, string $fieldName): mixed
    {
        return $this->ui->select(
            $this->ui->option('(unsigned)')
                ->selected(false)
                ->setValue(''),
            $this->ui->each($this->unsigned, fn($option) =>
                $this->ui->option($option)
                    ->selected($column->values()->unsigned === $option)
                    ->setValue($option)
            )
        )->setName($fieldName)
            ->setDataField('unsigned')
            ->when($column->field()->unsignedHidden, fn($input) => $input->setReadonly('readonly'));
    }

    /**
     * @param ColumnEntity $column
     * @param string $fieldName
     *
     * @return mixed
     */
    protected function getColumnOnDeleteField(ColumnEntity $column, string $fieldName): mixed
    {
        return $this->ui->select(
            $this->ui->option('(' . $this->trans->lang('ON DELETE') . ')')
                ->setValue('')
                ->selected(false),
            $this->ui->each($this->options['onDelete'], fn($option) =>
                $this->ui->option($option)
                    ->setValue($option)
                    ->selected($column->values()->onDelete === $option)
            )
        )->setName($fieldName)
            ->setDataField('onDelete')
            ->when($column->field()->onDeleteHidden, fn($input) => $input->setReadonly('readonly'));
    }

    /**
     * @param ColumnEntity $column
     * @param string $hasFieldName
     * @param string $fieldName
     * @param string $placeholder
     *
     * @return mixed
     */
    protected function getColumnDefaultField(ColumnEntity $column, string $hasFieldName,
        string $fieldName, string $placeholder = ''): mixed
    {
        return $this->ui->inputGroup(
            $this->ui->checkbox()
                ->checked($column->values()->hasDefault)
                ->setName($hasFieldName)
                ->setDataField('hasDefault')
                ->when($this->listMode, fn($input) => $this->disable($input, false)),
            $this->ui->input()
                ->setName($fieldName)
                ->setDataField('default')
                ->when($placeholder !== '', fn($input) => $input->setPlaceholder($placeholder))
                ->setValue($column->values()->default)
                ->when($this->listMode, fn($input) => $this->disable($input))
        );
    }
}
