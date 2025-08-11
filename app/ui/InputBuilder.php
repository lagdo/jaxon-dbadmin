<?php

namespace Lagdo\DbAdmin\Ui;

use Lagdo\DbAdmin\Translator;
use Lagdo\UiBuilder\BuilderInterface;

use function htmlentities;

class InputBuilder
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
    protected function bool(array $input): mixed
    {
        return $this->ui->div(
            $this->ui->input()
                ->setName($input['attrs']['name'])
                ->setValue('0')
                ->setType('hidden'),
            $this->ui->checkbox()
                ->checked($input['attrs']['checked'])
                ->setName($input['attrs']['name'])
                ->setValue('1')
        )->setClass('checkbox');
    }

    /**
     * @param array $input
     *
     * @return mixed
     */
    protected function checkbox(array $input): mixed
    {
        return $this->ui->div(
            $this->ui->each($input['values'], function($value) use($input) {
                $name = $input['attrs']['name'] . '[' . $value['value'] . ']';
                return $this->ui->label(
                    $this->ui->text($value['text'])->setFor($name),
                    $this->ui->checkbox()
                        ->checked($value['checked'])->setName($name)
                );
            })
        )
        ->setClass('checkbox');
    }

    /**
     * @param array $input
     *
     * @return mixed
     */
    protected function file(array $input): mixed
    {
        return $this->ui->formInput($input['attrs'])->setType('file');
    }

    /**
     * @param array $input
     *
     * @return mixed
     */
    protected function input(array $input): mixed
    {
        return $this->ui->formInput($input['attrs']);
    }

    /**
     * @param array $input
     *
     * @return mixed
     */
    protected function radio(array $input): mixed
    {
        return $this->ui->div(
            $this->ui->each($input['values'], fn($value) =>
                $this->ui->label(
                    $this->ui->text($value['text']),
                    $this->ui->checkbox($input['attrs'])
                        ->checked($value['checked'])
                        ->setValue($value['value'])
                )
            )
        )->setClass('radio');
    }

    /**
     * @param array $input
     *
     * @return mixed
     */
    protected function textarea(array $input): mixed
    {
        return $this->ui->formTextarea($input['attrs'],
            $this->ui->text($input['value']));
    }

    /**
     * @param string $type
     * @param array $input
     *
     * @return mixed
     */
    public function build(string $type, array $input): mixed
    {
        return $this->$type($input);
    }

    /**
     * @param array $options
     * @param string $optionClass
     * @param bool $useKeys
     *
     * @return string
     */
    public function htmlSelect(array $options, string $optionClass, bool $useKeys = false): string
    {
        return $this->ui->build(
            $this->ui->formSelect(
                $this->ui->each($options, fn($label, $key) =>
                    $this->ui->option($label)
                        ->selected(false)
                        ->setClass($optionClass)
                        ->setValue(htmlentities($useKeys ? $key : $label))
                )
            )
        );
    }
}
