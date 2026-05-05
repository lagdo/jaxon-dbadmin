<?php

namespace Lagdo\DbAdmin\App\Ui\Table\Column;

use Jaxon\Script\JsExpr;
use Lagdo\DbAdmin\App\Ui\Tab\Tab;
use Lagdo\DbAdmin\Support\Driver\UiDto\Ddl\ColumnDdDto;
use Lagdo\UiBuilder\BuilderInterface;
use Lagdo\UiBuilder\Component\HtmlComponent;

use function is_array;
use function is_string;
use function Jaxon\form;
use function Jaxon\jq;
use function strcasecmp;

trait ColumnTrait
{
    use MetadataTrait;

    /**
     * @var string
     */
    protected string $formColumnClass = 'dbadmin-table-columns-form-column';

    /**
     * @var array<string>
     */
    protected array $table = [];

    /**
     * @var array<ColumnDdDto>
     */
    protected array $inputs = [];

    /**
     * @var bool
     */
    protected bool $listMode = true;

    /**
     * @var BuilderInterface
     */
    protected BuilderInterface $ui;

    /**
     * @return Tab
     */
    abstract protected function tab(): Tab;

    /**
     * @return string
     */
    protected function tableFormId(): string
    {
        return $this->tab()->app()->id('dbadmin-table-values-form');
    }

    /**
     * @return string
     */
    protected function columnsFormId(): string
    {
        return $this->tab()->app()->id('dbadmin-table-columns-form');
    }

    /**
     * @return array
     */
    public function tableFormValues(): array
    {
        return form($this->tableFormId());
    }

    /**
     * @return JsExpr
     */
    public function columnsFormItemCount(): JsExpr
    {
        $formId = $this->columnsFormId();
        return jq(".{$this->formColumnClass}", "#{$formId}")->length;
    }

    /**
     * @return string
     */
    protected function editFormId(): string
    {
        return $this->tab()->app()->id('dbadmin-table-column-edit-form');
    }

    /**
     * @return array
     */
    public function editFormValues(): array
    {
        return form($this->editFormId());
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
            $style = $component->getAttribute('style') ?: '';
            $component->setStyle("background-color: #f8f8f8; $style");
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
            $this->ui->each($this->engines(), fn($engine) =>
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
                ->setValue('')
                ->selected(false),
            $this->ui->each($this->collations(), fn($_collations, $group) =>
                $this->ui->list(
                    $this->ui->when(is_string($_collations), fn() =>
                        $this->ui->option($_collations)
                            ->selected($currentCollation === $_collations)
                    ),
                    $this->ui->when(is_array($_collations) && $group === '', fn() =>
                        $this->ui->each($_collations, fn($collation) =>
                            $this->ui->option($collation)
                                ->selected($currentCollation === $collation)
                        )
                    ),
                    $this->ui->when(is_array($_collations) && $group !== '', fn() =>
                        $this->ui->optgroup(
                            $this->ui->each($_collations, fn($collation) =>
                                $this->ui->option($collation)
                                    ->selected($currentCollation === $collation)
                            ),
                        )->setLabel($group)
                    )
                )
            )
        );
    }

    /**
     * @param ColumnDdDto $input
     * @param string $columnName
     *
     * @return mixed
     */
    protected function getColumnNameField(ColumnDdDto $input, string $columnName): mixed
    {
        return $this->ui->input(['class' => 'column-name'])
            ->setName($columnName)
            ->setValue($input->values()->name)
            ->setDataField('name')
            ->setDataMaxlength('64')
            ->setAutocapitalize('off');
    }

    /**
     * @param ColumnDdDto $input
     * @param string $columnName
     *
     * @return mixed
     */
    protected function getColumnPrimaryField(ColumnDdDto $input, string $columnName): mixed
    {
        return $this->ui->checkbox()
            ->checked($input->values()->primary)
            ->setName($columnName)
            ->setValue('1');
    }

    /**
     * @param ColumnDdDto $input
     * @param string $columnName
     *
     * @return mixed
     */
    protected function getColumnAutoIncrementField(ColumnDdDto $input, string $columnName): mixed
    {
        return $this->ui->checkbox()
            ->checked($input->values()->autoIncrement)
            ->setName($columnName)
            ->setValue('1');
    }

    /**
     * @param ColumnDdDto $input
     * @param string $columnName
     *
     * @return mixed
     */
    protected function getColumnCollationField(ColumnDdDto $input, string $columnName): mixed
    {
        return $this->getCollationSelect($input->values()->collation)
            ->setName($columnName)
            ->setDataField('collation')
            ->when($input->collationEditable, fn($input) => $input->setReadonly('readonly'));
    }

    /**
     * @param ColumnDdDto $input
     * @param string $columnName
     *
     * @return mixed
     */
    protected function getColumnOnUpdateField(ColumnDdDto $input, string $columnName): mixed
    {
        return $this->ui->select(
            $this->ui->option('(' . $this->trans->lang('ON UPDATE') . ')')
                ->setValue('')->selected(false),
            $this->ui->each($this->options()['onUpdate'], fn($option, $value) =>
                $this->ui->option($option)
                    ->selected($input->values()->onUpdate === $option)
                    ->setValue($value)
            )
        )->setName($columnName)
            ->setDataField('onUpdate')
            ->when($input->onUpdateEditable, fn($input) => $input->setReadonly('readonly'));
    }

    /**
     * @param ColumnDdDto $input
     * @param string $columnName
     * @param string $hasFieldName
     * @param string $placeholder
     * @param bool $disabled
     *
     * @return mixed
     */
    protected function getColumnCommentField(ColumnDdDto $input, string $columnName,
        string $hasFieldName, string $placeholder = '', bool $disabled = false): mixed
    {
        return $this->ui->inputGroup(
            $this->ui->checkbox()
                ->checked($input->values()->setComment)
                ->setName($hasFieldName)
                ->setValue('1')
                ->when($disabled, fn($checkbox) => $this->disable($checkbox, false)),
            $this->ui->input()
                ->setType('text')
                ->setName($columnName)
                ->setValue($input->values()->comment ?? '')
                ->setDataField('comment')
                ->setPlaceholder($placeholder)
                ->when($disabled, fn($input) => $this->disable($input, true))
        );
    }

    /**
     * @param ColumnDdDto $input
     * @param string $columnName
     *
     * @return mixed
     */
    protected function getColumnTypeField(ColumnDdDto $input, string $columnName): mixed
    {
        return $this->ui->select(
            $this->ui->each($input->types, fn($groupTypes, $groupName) =>
                $this->ui->pick([
                    !is_numeric($groupName),
                    fn() => $this->ui->optgroup(
                        $this->ui->each($groupTypes, fn($type, $key) =>
                            $this->ui->option($type)
                                ->selected($input->values()->type === $type)
                                ->when(!is_numeric($key), fn($input) => $input->setValue($key))
                        )
                    )->setLabel($groupName)
                ], [
                    is_array($groupTypes),
                    fn() => $this->ui->each($groupTypes, fn($type, $key) =>
                        $this->ui->option($type)
                            ->selected($input->values()->type === $type)
                            ->when(!is_numeric($key), fn($input) => $input->setValue($key)))
                ], [
                    $type = $groupTypes, // Always true
                    $this->ui->option($type)
                        ->selected($input->values()->type === $type)
                ])
            )
        )->setName($columnName)
            ->setDataField('type');
    }

    /**
     * @param ColumnDdDto $input
     * @param string $columnName
     *
     * @return mixed
     */
    protected function getColumnLengthField(ColumnDdDto $input, string $columnName): mixed
    {
        return $this->ui->input()
            ->setStyle('width: 100%')
            ->setName($columnName)
            ->setPlaceholder($this->trans->lang('Length'))
            ->setDataField('length')
            ->setSize('3')
            ->setValue($input->values()->length ?: '');
    }

    /**
     * @param ColumnDdDto $input
     * @param string $columnName
     *
     * @return mixed
     */
    protected function getColumnNullableField(ColumnDdDto $input, string $columnName): mixed
    {
        return $this->ui->checkbox()
            ->checked($input->values()->nullable)
            ->setName($columnName)
            ->setDataField('null')
            ->setValue('1');
    }

    /**
     * @param ColumnDdDto $input
     * @param string $columnName
     *
     * @return mixed
     */
    protected function getColumnUnsignedField(ColumnDdDto $input, string $columnName): mixed
    {
        return $this->ui->select(
            $this->ui->option('(unsigned)')
                ->selected(false)
                ->setValue(''),
            $this->ui->each($this->unsigned(), fn($option) =>
                $this->ui->option($option)
                    ->selected($input->values()->unsigned === $option)
                    ->setValue($option)
            )
        )->setName($columnName)
            ->setDataField('unsigned')
            ->when($input->unsignedEditable, fn($input) => $input->setReadonly('readonly'));
    }

    /**
     * @param ColumnDdDto $input
     * @param string $columnName
     *
     * @return mixed
     */
    protected function getColumnOnDeleteField(ColumnDdDto $input, string $columnName): mixed
    {
        return $this->ui->select(
            $this->ui->option('(' . $this->trans->lang('ON DELETE') . ')')
                ->setValue('')
                ->selected(false),
            $this->ui->each($this->options()['onDelete'], fn($option) =>
                $this->ui->option($option)
                    ->setValue($option)
                    ->selected($input->values()->onDelete === $option)
            )
        )->setName($columnName)
            ->setDataField('onDelete')
            ->when($input->onDeleteEditable, fn($input) => $input->setReadonly('readonly'));
    }

    /**
     * @param ColumnDdDto $input
     * @param string $generated     The name of the generated input field
     * @param string $default       The name of the default value input field
     * @param string $placeholder
     *
     * @return mixed
     */
    protected function getColumnDefaultField(ColumnDdDto $input, string $generated,
        string $default, string $placeholder = ''): mixed
    {
        return $this->ui->inputGroup(
            $this->ui->select(
                $this->ui->each($this->defaults(), fn($default) =>
                    $this->ui->option($default)
                        ->selected($input->values()->generated === $default))
            )->setName($generated)
                ->setDataField('generated')
                ->setStyle('width: 30%;')
                ->when($this->listMode, fn($input) => $this->disable($input, false)),
            $this->ui->input()
                ->setName($default)
                ->setDataField('default')
                ->setStyle('width: 70%;')
                ->when($placeholder !== '', fn($input) => $input->setPlaceholder($placeholder))
                ->setValue($input->values()->default)
                ->when($this->listMode, fn($input) => $this->disable($input))
        );
    }
}
