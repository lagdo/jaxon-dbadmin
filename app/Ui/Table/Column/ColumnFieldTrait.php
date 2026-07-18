<?php

namespace Lagdo\DbAdmin\App\Ui\Table\Column;

use Jaxon\Script\JsExpr;
use Lagdo\DbAdmin\App\Ui\Tab\Tab;
use Lagdo\DbAdmin\Support\Driver\UiDto\Ddl\ColumnFormDto;
use Lagdo\UiBuilder\BuilderInterface;
use Lagdo\UiBuilder\HtmlComponent;

use function array_filter;
use function array_values;
use function is_array;
use function is_string;
use function Jaxon\jq;
use function Jaxon\pm;
use function strcasecmp;

trait ColumnFieldTrait
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
     * @return array<array>
     */
    protected function getColumnTypes(): array
    {
        $types = $this->engine()->structuredTypes();
        $unsignedTypes = array_values($types[$this->trans->lang('Numbers')] ?? []);
        $collationTypes = array_filter($types[$this->trans->lang('Strings')] ?? [],
            fn(string $type) => $type !== 'json' && $type !== 'uuid');
        $onUpdateTypes = ['datetime', 'timestamp'];
        return [$unsignedTypes, array_values($collationTypes), $onUpdateTypes];
    }

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
     * @param string $columnId
     *
     * @return string
     */
    public function columnDivId(string $columnId): string
    {
        return $this->tab()->app()->id("dbadmin-table-column-$columnId");
    }

    /**
     * @return array
     */
    public function tableFormValues(): array
    {
        return pm()->form($this->tableFormId());
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
    public function editFormId(): string
    {
        return $this->tab()->app()->id('dbadmin-table-column-edit-form');
    }

    /**
     * @return array
     */
    public function editFormValues(): array
    {
        return pm()->form($this->editFormId());
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
     * @param ColumnFormDto $input
     * @param string $columnName
     *
     * @return mixed
     */
    protected function getColumnNameField(ColumnFormDto $input, string $columnName): mixed
    {
        return $this->ui->input(['class' => 'column-name'])
            ->setName($columnName)
            ->setValue($input->values()->name)
            ->setDataMaxlength('64')
            ->setAutocapitalize('off');
    }

    /**
     * @param ColumnFormDto $input
     * @param string $columnName
     *
     * @return mixed
     */
    protected function getColumnPrimaryField(ColumnFormDto $input, string $columnName): mixed
    {
        return $this->ui->checkbox()
            ->checked($input->values()->primary)
            ->setName($columnName)
            ->setValue('1');
    }

    /**
     * @param ColumnFormDto $input
     * @param string $columnName
     *
     * @return mixed
     */
    protected function getColumnAutoIncrementField(ColumnFormDto $input, string $columnName): mixed
    {
        return $this->ui->checkbox()
            ->checked($input->values()->autoIncrement)
            ->setName($columnName)
            ->setValue('1');
    }

    /**
     * @param ColumnFormDto $input
     * @param string $columnName
     *
     * @return mixed
     */
    protected function getColumnCollationField(ColumnFormDto $input, string $columnName): mixed
    {
        return $this->getCollationSelect($input->values()->collation)
            ->setName($columnName);
    }

    /**
     * @param ColumnFormDto $input
     * @param string $columnName
     *
     * @return mixed
     */
    protected function getColumnOnUpdateField(ColumnFormDto $input, string $columnName): mixed
    {
        return $this->ui->select(
            $this->ui->option('(' . $this->trans->lang('ON UPDATE') . ')')
                ->setValue('')->selected(false),
            $this->ui->each($this->columnOptions('onUpdate'), fn($option, $value) =>
                $this->ui->option($option)
                    ->selected($input->values()->onUpdate === $option)
                    ->setValue($value)
            )
        )->setName($columnName);
    }

    /**
     * @param ColumnFormDto $input
     * @param string $columnName
     * @param string $hasFieldName
     * @param string $placeholder
     * @param bool $disabled
     *
     * @return mixed
     */
    protected function getColumnCommentField(ColumnFormDto $input, string $columnName,
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
                ->setPlaceholder($placeholder)
                ->when($disabled, fn($input) => $this->disable($input, true))
        );
    }

    /**
     * @param ColumnFormDto $input
     * @param string $columnName
     *
     * @return mixed
     */
    protected function getColumnTypeField(ColumnFormDto $input, string $columnName): mixed
    {
        return $this->ui->select(
            $this->ui->each($input->types, fn($groupTypes, $groupName) =>
                $this->ui->pick(
                    $this->ui->when(!is_numeric($groupName), fn() =>
                        $this->ui->optgroup(
                            $this->ui->each($groupTypes, fn($type, $key) =>
                                $this->ui->option($type)
                                    ->selected($input->values()->type === $type)
                                    ->when(!is_numeric($key), fn($input) => $input->setValue($key))
                            )
                        )->setLabel($groupName)
                    ),
                    $this->ui->when(is_array($groupTypes), fn() =>
                        $this->ui->each($groupTypes, fn($type, $key) =>
                            $this->ui->option($type)
                                ->selected($input->values()->type === $type)
                                ->when(!is_numeric($key), fn($input) => $input->setValue($key))
                        )
                    ),
                    $this->ui->when(!!($type = $groupTypes) /* Assign, always true */, fn() =>
                        $this->ui->option($type)
                            ->selected($input->values()->type === $type)
                    )
                )
            )
        )->setName($columnName);
    }

    /**
     * @param ColumnFormDto $input
     * @param string $columnName
     *
     * @return mixed
     */
    protected function getColumnLengthField(ColumnFormDto $input, string $columnName): mixed
    {
        return $this->ui->input()
            ->setStyle('width: 100%')
            ->setName($columnName)
            ->setPlaceholder($this->trans->lang('Length'))
            ->setSize('3')
            ->setValue($input->values()->length ?: '');
    }

    /**
     * @param ColumnFormDto $input
     * @param string $columnName
     *
     * @return mixed
     */
    protected function getColumnNullableField(ColumnFormDto $input, string $columnName): mixed
    {
        return $this->ui->checkbox()
            ->checked($input->values()->nullable)
            ->setName($columnName)
            ->setValue('1');
    }

    /**
     * @param ColumnFormDto $input
     * @param string $columnName
     *
     * @return mixed
     */
    protected function getColumnUnsignedField(ColumnFormDto $input, string $columnName): mixed
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
        )->setName($columnName);
    }

    /**
     * @param ColumnFormDto $input
     * @param string $generated     The name of the generated input field
     * @param string $default       The name of the default value input field
     * @param string $placeholder
     *
     * @return mixed
     */
    protected function getColumnDefaultField(ColumnFormDto $input, string $generated,
        string $default, string $placeholder = ''): mixed
    {
        return $this->ui->inputGroup(
            $this->ui->select(
                $this->ui->each($this->defaults(), fn($default) =>
                    $this->ui->option($default)
                        ->selected($input->values()->generated === $default))
            )->setName($generated)
                ->setStyle('width: 30%;')
                ->when($this->listMode, fn($input) => $this->disable($input, false)),
            $this->ui->input()
                ->setName($default)
                ->setStyle('width: 70%;')
                ->when($placeholder !== '', fn($input) => $input->setPlaceholder($placeholder))
                ->setValue($input->values()->default)
                ->when($this->listMode, fn($input) => $this->disable($input))
        );
    }

    /**
     * @param ColumnFormDto $input
     * @param string $columnName
     *
     * @return mixed
     */
    protected function getColumnForeignKeyField(ColumnFormDto $input, string $columnName): mixed
    {
        return $this->ui->select(
            $this->ui->option('')
                ->selected(false),
            $this->ui->each($this->referencableColumns(), fn(string $label, string $value) =>
                $this->ui->option($label)
                    ->setValue($value)
                    // The final value of the foreign key id must be used here.
                    ->selected($input->fkIdValue() === $value)
            )
        )->setName($columnName);
    }

    /**
     * @param ColumnFormDto $input
     * @param string $columnName
     *
     * @return mixed
     */
    protected function getColumnForeignKeyInput(ColumnFormDto $input, string $columnName): mixed
    {
        return $this->ui->input()
            // The original value of the foreign key id can be used here.
            ->setValue($this->referencableColumn($input->fkId()))
            ->setName($columnName);
    }

    /**
     * @param ColumnFormDto $input
     * @param string $columnName
     *
     * @return mixed
     */
    protected function getForeignKeyOnUpdateField(ColumnFormDto $input, string $columnName): mixed
    {
        return $this->ui->select(
            $this->ui->option('')
                ->selected(false),
            $this->ui->each($this->foreignKeyOptions('onUpdate'), fn($option) =>
                $this->ui->option($option)
                    ->setValue($option)
                    ->selected($input->fkOnUpdate() === $option)
            )
        )->setName($columnName);
    }

    /**
     * @param ColumnFormDto $input
     * @param string $columnName
     *
     * @return mixed
     */
    protected function getForeignKeyOnDeleteField(ColumnFormDto $input, string $columnName): mixed
    {
        return $this->ui->select(
            $this->ui->option('')
                ->selected(false),
            $this->ui->each($this->foreignKeyOptions('onDelete'), fn($option) =>
                $this->ui->option($option)
                    ->setValue($option)
                    ->selected($input->fkOnDelete() === $option)
            )
        )->setName($columnName);
    }

    /**
     * @param ColumnFormDto $input
     * @param string $columnName
     *
     * @return mixed
     */
    protected function getForeignKeyDeferrableField(ColumnFormDto $input, string $columnName): mixed
    {
        return $this->ui->checkbox()
            ->checked($input->fkDeferrable())
            ->setName($columnName)
            ->setValue('1');
    }
}
