<?php

namespace Lagdo\DbAdmin\Ui;

use Lagdo\DbAdmin\Translator;
use Lagdo\UiBuilder\BuilderInterface;

use function htmlentities;

class InputBuilder
{
    /**
     * @param Translator $trans
     * @param BuilderInterface $html
     */
    public function __construct(protected Translator $trans, protected BuilderInterface $html)
    {}

    /**
     * @param array $input
     *
     * @return mixed
     */
    protected function bool(array $input): mixed
    {
        return $this->html->div(
            $this->html->input()
                ->setName($input['attrs']['name'])
                ->setValue('0')
                ->setType('hidden'),
            $this->html->checkbox()
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
        return $this->html->div(
            $this->html->each($input['values'], function($value) use($input) {
                $name = $input['attrs']['name'] . '[' . $value['value'] . ']';
                return $this->html->label(
                    $this->html->text($value['text'])->setFor($name),
                    $this->html->checkbox()
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
        return $this->html->formInput($input['attrs'])->setType('file');
    }

    /**
     * @param array $input
     *
     * @return mixed
     */
    protected function input(array $input): mixed
    {
        return $this->html->formInput($input['attrs']);
    }

    /**
     * @param array $input
     *
     * @return mixed
     */
    protected function radio(array $input): mixed
    {
        return $this->html->div(
            $this->html->each($input['values'], fn($value) =>
                $this->html->label(
                    $this->html->text($value['text']),
                    $this->html->checkbox($input['attrs'])
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
        return $this->html->formTextarea($input['attrs'],
            $this->html->text($input['value']));
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
        return $this->html->build(
            $this->html->formSelect(
                $this->html->each($options, fn($label, $key) =>
                    $this->html->option($label)
                        ->selected(false)
                        ->setClass($optionClass)
                        ->setValue(htmlentities($useKeys ? $key : $label))
                )
            )
        );
    }
}
