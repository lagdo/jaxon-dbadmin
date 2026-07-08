<?php

namespace Lagdo\DbAdmin\App\Ui\Data;

use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dml\SearchFunc;
use Lagdo\DbAdmin\App\Ui\Tab\Tab;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dml\ColumnDmDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dql\QueryResultRowDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dql\SelectRowsetDto;
use Lagdo\DbAdmin\Support\Translator;
use Lagdo\UiBuilder\BuilderInterface;

use function Jaxon\input;
use function Jaxon\jo;
use function Jaxon\rq;

class EditUiBuilder
{
    /**
     * @param Translator $trans
     * @param BuilderInterface $ui
     * @param Tab $tab
     */
    public function __construct(protected Translator $trans,
        protected BuilderInterface $ui, protected Tab $tab)
    {}

    /**
     * @return Tab
     */
    protected function tab(): Tab
    {
        return $this->tab;
    }

    /**
     * @param array $input
     *
     * @return mixed
     */
    protected function getEnumValueInput(array $input): mixed
    {
        return $this->ui->each($input['items'], fn($item) =>
            $this->ui->label(
                $this->ui->radio($item['attrs'])
                    ->setValue($item['value'], false)
                    ->when($item['checked'], fn($radio) =>
                        $radio->setAttribute('checked', 'checked'))
                    ->setStyle('margin-right:3px;'),
                $this->ui->span($item['label'])
            )->setFor($this->tab()->app()->id($item['attrs']['id']))
                ->setStyle('margin-right:7px;')
        );
    }

    /**
     * @param array $input
     *
     * @return mixed
     */
    protected function getSetValueInput(array $input): mixed
    {
        return $this->ui->each($input['items'], fn($item) =>
            $this->ui->label(
                $this->ui->checkbox($item['attrs'])
                    ->checked($item['checked'])
                    ->setValue($item['value'], false)
                    ->setStyle('margin-right:3px;'),
                $this->ui->span($item['label'])
            )->setFor($this->tab()->app()->id($item['attrs']['id']))
                ->setStyle('margin-right:7px;')
        );
    }

    /**
     * @param array $input
     *
     * @return mixed
     */
    protected function getBoolValueInput(array $input): mixed
    {
        return $this->ui->list(
            $this->ui->input($input['attrs']['hidden'])
                ->setType('hidden'),
            $this->ui->checkbox($input['attrs']['checkbox'])
                ->checked($input['checked'])
        );
    }

    /**
     * @param array $input
     *
     * @return mixed
     */
    protected function getFileValueInput(array $input): mixed
    {
        return $this->ui->input($input['attrs'])
            ->setType('file');
    }

    /**
     * @param array $input
     *
     * @return mixed
     */
    protected function getJsonValueInput(array $input): mixed
    {
        return $this->ui->textarea($input['value'], $input['attrs']);
    }

    /**
     * @param array $input
     *
     * @return mixed
     */
    protected function getTextValueInput(array $input): mixed
    {
        return $this->ui->textarea($input['value'], $input['attrs']);
    }

    /**
     * @param array $input
     *
     * @return mixed
     */
    protected function getDefaultValueInput(array $input): mixed
    {
        return $this->ui->input($input['attrs'])
            ->setValue($input['value'], false)
            ->addClass('no-arrows');
    }

    /**
     * @param ColumnDmDto $input
     *
     * @return mixed
     */
    protected function getColumnValue(ColumnDmDto $input): mixed
    {
        $input = $input->valueInput;
        return match($input['field']) {
            'enum' => $this->getEnumValueInput($input),
            'bool' => $this->getBoolValueInput($input),
            'set' => $this->getSetValueInput($input),
            'file' => $this->getFileValueInput($input),
            'json' => $this->getJsonValueInput($input),
            'text' => $this->getTextValueInput($input),
            default => $this->getDefaultValueInput($input),
        };
    }

    /**
     * @param ColumnDmDto $input
     *
     * @return mixed
     */
    private function getColumnFunction(ColumnDmDto $input): mixed
    {
        $input = $input->functionInput;
        return $this->ui->pick(
            $this->ui->when(isset($input['label']), fn() =>
                $this->ui->span($input['label'])
            ),
            $this->ui->when(isset($input['select']), fn() =>
                $this->ui->select(
                    $input['select']['attrs'],
                    $this->ui->each($input['select']['options'], fn($option) =>
                        $this->ui->option($option)
                            ->selected($option === $input['select']['value'])
                    )
                )
            ),
            $this->ui->when(true, fn() => $this->ui->html('')),
        );
    }

    /**
     * @param ColumnDmDto $input
     *
     * @return mixed
     */
    private function getColumnTitle(ColumnDmDto $input): mixed
    {
        return isset($input->valueInput['attrs']['id']) ?
            $this->ui->label($input->name)
                ->setFor($this->tab()->app()->id($input->valueInput['attrs']['id']))
                ->setTitle($input->type) :
            $this->ui->span($input->name)
                ->setTitle($input->type);
    }

    /**
     * @return string
     */
    public function searchListId(string $columnName): string
    {
        return $this->tab()->app()->id("dbadmin-table-foreign-search_list_$columnName");
    }

    /**
     * @return string
     */
    public function searchValueId(string $columnName): string
    {
        return $this->tab()->app()->id("dbadmin-table-foreign-search_value_$columnName");
    }

    /**
     * @param string $table
     * @param ColumnDmDto $input
     *
     * @return mixed
     */
    private function getAutocompleteColumn(string $table, ColumnDmDto $input): mixed
    {
        $columnName = $input->column->name;
        $inputId = $this->tab()->app()->id("dbadmin-table-foreign-search_input_$columnName");

        return $this->ui->div(
            $this->ui->input()
                ->setType('text')
                ->setId($inputId)
                ->setClass('search-box')
                ->setStyle("anchor-name: --anchor-$columnName;")
                ->setPlaceholder("Search in table {$input->foreignKey->table}")
                ->setAutocomplete('off')
                ->jxnOn('input', rq(SearchFunc::class)->search($table,
                    $columnName, input($inputId))->debounce('search')),
            $this->ui->div()
                ->setPopover('')
                ->setId($this->searchListId($columnName))
                ->addClass('jaxon-dbadmin-foreign-column-search-list')
                ->setStyle("position-anchor: --anchor-$columnName;")
        )->setClass('autocomplete-search-box');
    }

    /**
     * @param SelectRowsetDto $rowset
     * @param string $columnName
     *
     * @return string
     */
    public function getAutocompleteList(SelectRowsetDto $rowset, string $columnName): string
    {
        $valueId = $this->searchValueId($columnName);

        return $this->ui->build(
            $this->ui->menu(
                $this->ui->each($rowset->rows, fn(QueryResultRowDto $row) =>
                    $this->ui->menuItem($this->ui->html($row->columns[1]['html']))
                        ->jxnClick(jo('jaxon.dbadmin')
                            ->setForeignColumnValue($valueId, $row->columns[0]['value']))
                )
            )->setClass('search-result')
        );
    }

    /**
     * @return string
     */
    public function queryFormId(): string
    {
        return $this->tab()->app()->id('dbadmin-table-query-form');
    }

    /**
     * @param string $table
     * @param array<ColumnDmDto> $inputs
     *
     * @return string
     */
    public function rowDataForm(string $table, array $inputs): string
    {
        return $this->ui->build(
            $this->ui->form(
                $this->ui->each($inputs, fn(ColumnDmDto $input) =>
                    $this->ui->row(
                        $this->ui->col($this->getColumnTitle($input))
                            ->width(3),
                        $this->ui->col($this->getColumnFunction($input))
                            ->setStyle('padding-left: 1px; padding-right: 1px;')
                            ->width(2),
                        $this->ui->when($input->foreignKey === null, fn() =>
                            $this->ui->col($this->getColumnValue($input))
                                ->setStyle('padding-left: 1px;')
                                ->width(7)
                        ),
                        $this->ui->when($input->foreignKey !== null, fn() =>
                            $this->ui->list(
                                $this->ui->col(
                                    $this->getColumnValue($input)
                                        ->setId($this->searchValueId($input->column->name))
                                )->setStyle('padding-left: 1px; padding-right: 1px;')
                                    ->width(2),
                                $this->ui->col($this->getAutocompleteColumn($table, $input))
                                    ->setStyle('padding-left: 1px;')
                                    ->width(5)
                            )
                        )
                    )
                )
            )->setId($this->queryFormId())
        );
    }

    /**
     * @return string
     */
    public function queryDivId(): string
    {
        return $this->tab()->app()->id('dbadmin-table-show-sql-query');
    }

    /**
     * @param string $queryText
     *
     * @return string
     */
    public function sqlCodeElement(string $queryText): string
    {
        return $this->ui->build(
            $this->ui->row(
                $this->ui->col(
                    $this->ui->card(
                        $this->ui->cardBody(
                            $this->ui->div($queryText)
                                ->setId($this->queryDivId())
                                ->setStyle('height: 300px;')
                        )->setStyle('padding: 0 1px;')
                    )->setStyle('padding: 5px;')
                )->width(12)
            )
        );
    }
}
