<?php

namespace Lagdo\DbAdmin\Ui\Data;

use Lagdo\DbAdmin\Db\Page\Dml\FieldEditEntity;
use Lagdo\DbAdmin\Db\Translator;
use Lagdo\UiBuilder\BuilderInterface;

class EditUiBuilder
{
    /**
     * @param Translator $trans
     * @param BuilderInterface $ui
     */
    public function __construct(protected Translator $trans, protected BuilderInterface $ui)
    {}

    /**
     * @param array $input
     *
     * @return mixed
     */
    protected function getEnumValueInput(array $input): mixed
    {
        return $this->ui->list(
            $this->ui->when(isset($input['orig']), fn() =>
                $this->ui->label(
                    $this->ui->radio($input['orig']['attrs']),
                    $this->ui->html($input['orig']['label'])
                )
            ),
            $this->ui->each($input['items'], fn($item) =>
                $this->ui->label(
                    $this->ui->radio($item['attrs']),
                    $this->ui->html($item['label'])
                )
            )
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
            $this->ui->input($input['hidden']['attrs']),
            $this->ui->checkbox($input['checkbox']['attrs'])
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
                $this->ui->checkbox($item['attrs']),
                $this->ui->html($item['label'])
            )
        );
    }

    /**
     * @param array $input
     *
     * @return mixed
     */
    protected function getFileValueInput(array $input): mixed
    {
        return $this->ui->formInput($input['attrs']);
    }

    /**
     * @param array $input
     *
     * @return mixed
     */
    protected function getJsonValueInput(array $input): mixed
    {
        return $this->ui->formTextarea($input['value'], $input['attrs']);
    }

    /**
     * @param array $input
     *
     * @return mixed
     */
    protected function getTextValueInput(array $input): mixed
    {
        return $this->ui->formTextarea($input['value'], $input['attrs']);
    }

    /**
     * @param array $input
     *
     * @return mixed
     */
    protected function getDefaultValueInput(array $input): mixed
    {
        return $this->ui->formInput($input['attrs']);
    }

    /**
     * @param array $input
     *
     * @return mixed
     */
    protected function getValueInput(array $input): mixed
    {
        return match($input['type']) {
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
     * @param array|null $input
     *
     * @return mixed
     */
    private function getFunctionInput(array|null $input): mixed
    {
        return $input === null ?
            $this->ui->text('') :
            $this->ui->list(
                $this->ui->when($input['type'] === 'name', fn() =>
                    $this->ui->span($input['label'])
                ),
                $this->ui->when($input['type'] === 'select', fn() =>
                    $this->ui->formSelect(
                        $input['attrs'],
                        $this->ui->each($input['options'], fn($option) =>
                            $this->ui->option($option)
                                ->selected($option === $input['value'])
                        )
                    )
                )
            );
    }

    /**
     * @param string $formId
     * @param array<FieldEditEntity> $fields
     * @param string $maxHeight
     *
     * @return string
     */
    public function rowDataForm(string $formId, array $fields, string $maxHeight = ''): string
    {
        $form = $this->ui->form(
            $this->ui->each($fields, fn(FieldEditEntity $field) =>
                $this->ui->formRow(
                    $this->ui->formCol(
                        $this->ui->label($field->name)->setTitle($field->type)
                    )->width(3),
                    $this->ui->formCol(
                        $this->getFunctionInput($field->functionInput)
                    )->width(2),
                    $this->ui->formCol(
                        $this->getValueInput($field->valueInput)
                    )->width(7)
                )
            )
        )->responsive(true)->wrapped(false)->setId($formId);

        return $maxHeight === '' ?
            $this->ui->build($form) :
            $this->ui->build(
                $this->ui->div($form)
                    ->setStyle("max-height:$maxHeight; overflow-x:hidden; overflow-y:scroll;")
            );
    }
}
