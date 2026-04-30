<?php

namespace Lagdo\DbAdmin\App\Ui\Data;

use Lagdo\DbAdmin\App\Ui\Tab\Tab;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dml\DmInputDto;
use Lagdo\DbAdmin\Support\Translator;
use Lagdo\UiBuilder\BuilderInterface;

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
        return $this->ui->list(
            $this->ui->each($input['items'], fn($item) =>
                $this->ui->label(
                    $this->ui->radio($item['attrs'])
                        ->setValue($item['value'], false)
                        ->when($item['checked'], fn($radio) =>
                            $radio->setAttribute('checked', 'checked'))
                        ->setStyle('margin-right:3px;'),
                    $this->ui->span($item['label'])
                )->setFor($this->tab()->app()->id($item['attrs']['id']))
                    ->setStyle('margin-right:7px;')
            )
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
                    ->setValue($item['value'], false)
                    ->when($item['checked'], fn($checkbox) =>
                        $checkbox->setAttribute('checked', 'checked'))
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
                ->when($input['checked'], fn($checkbox) =>
                    $checkbox->setAttribute('checked', 'checked'))
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
            ->setValue($input['value'], false);
    }

    /**
     * @param DmInputDto $input
     *
     * @return mixed
     */
    protected function getColumnValue(DmInputDto $input): mixed
    {
        $input = $input->valueInput;
        return match($input['column']) {
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
     * @param DmInputDto $input
     *
     * @return mixed
     */
    private function getColumnFunction(DmInputDto $input): mixed
    {
        $input = $input->functionInput;
        return $this->ui->pick(
            [isset($input['label']), fn() => $this->ui->span($input['label'])],
            [isset($input['select']), fn() => $this->ui->select(
                $input['select']['attrs'],
                $this->ui->each($input['select']['options'], fn($option) =>
                    $this->ui->option($option)
                        ->selected($option === $input['select']['value'])
                )
            )],
            [true, fn() => $this->ui->html('')],
        );
        // return $this->ui->list(match(true) {
        //     isset($input['label']) => $this->ui->span($input['label']),
        //     isset($input['select']) => $this->ui->select(
        //         $input['select']['attrs'],
        //         $this->ui->each($input['select']['options'], fn($option) =>
        //             $this->ui->option($option)
        //                 ->selected($option === $input['select']['value'])
        //         )
        //     ),
        //     default => $this->ui->text(''),
        // });
    }

    /**
     * @param DmInputDto $input
     *
     * @return mixed
     */
    public function getColumnTitle(DmInputDto $input): mixed
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
    public function queryFormId(): string
    {
        return $this->tab()->app()->id('dbadmin-table-query-form');
    }

    /**
     * @param array<DmInputDto> $inputs
     * @param string $maxHeight
     *
     * @return string
     */
    public function rowDataForm(array $inputs, string $maxHeight = ''): string
    {
        $form = $this->ui->form(
            $this->ui->each($inputs, fn(DmInputDto $input) =>
                $this->ui->row(
                    $this->ui->col(
                        $this->getColumnTitle($input)
                    )->width(3),
                    $this->ui->col(
                        $this->getColumnFunction($input)
                    )->width(2),
                    $this->ui->col(
                        $this->getColumnValue($input)
                    )->width(7)
                )
            )
        )->wrapped(false)->setId($this->queryFormId());

        return $maxHeight === '' ? $this->ui->build($form) :
            $this->ui->build(
                $this->ui->div($form)
                    ->setStyle("max-height:$maxHeight; overflow-x:hidden; overflow-y:scroll;")
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
                    $this->ui->panel(
                        $this->ui->panelBody(
                            $this->ui->div($queryText)
                                ->setId($this->queryDivId())
                                ->setStyle('height: 300px;')
                        )->setStyle('padding: 0 1px;')
                    )->look('default')
                        ->setStyle('padding: 5px;')
                )->width(12)
            )
        );
    }
}
