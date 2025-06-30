<?php

namespace Lagdo\DbAdmin\Ui;

use Lagdo\DbAdmin\Translator;
use Lagdo\UiBuilder\Jaxon\Builder;

class InputBuilder
{
    /**
     * @param Translator $trans
     */
    public function __construct(protected Translator $trans)
    {}

    /**
     * @param array $input
     *
     * @return void
     */
    protected function bool(array $input)
    {
        $htmlBuilder = Builder::new();
        $htmlBuilder
            ->div()->setClass('checkbox')
                ->input()
                    ->setName($input['attrs']['name'])
                    ->setValue('0')
                    ->setType('hidden')
                ->end()
                ->checkbox($input['attrs']['checked'])
                    ->setName($input['attrs']['name'])
                    ->setValue('1')
                ->end()
            ->end();
    }

    /**
     * @param array $input
     *
     * @return void
     */
    protected function checkbox(array $input)
    {
        $htmlBuilder = Builder::new();
        $htmlBuilder
            ->div()->setClass('checkbox');
        foreach ($input['values'] as $value) {
            $name = $input['attrs']['name'] . '[' . $value['value'] . ']';
            $htmlBuilder
                ->label()->setFor($name)
                    ->checkbox($value['checked'])->setName($name)
                    ->end()
                    ->addText($value['text'])
                ->end();
        }
        $htmlBuilder
            ->end();
    }

    /**
     * @param array $input
     *
     * @return void
     */
    protected function file(array $input)
    {
        $htmlBuilder = Builder::new();
        $htmlBuilder
            ->formInput($input['attrs'])->setType('file')
            ->end();
    }

    /**
     * @param array $input
     *
     * @return void
     */
    protected function input(array $input)
    {
        $htmlBuilder = Builder::new();
        $htmlBuilder
            ->formInput($input['attrs'])
            ->end();
    }

    /**
     * @param array $input
     *
     * @return void
     */
    protected function radio(array $input)
    {
        $htmlBuilder = Builder::new();
        $htmlBuilder
            ->div()->setClass('radio');
        foreach ($input['values'] as $value) {
            $htmlBuilder
                ->label()
                    ->checkbox($value['checked'], $input['attrs'])->setValue($value['value'])
                    ->end()
                    ->addText($value['text'])
                ->end();
        }
        $htmlBuilder
            ->end();
    }

    /**
     * @param array $input
     *
     * @return void
     */
    protected function textarea(array $input)
    {
        $htmlBuilder = Builder::new();
        $htmlBuilder
            ->formTextarea($input['attrs'])
                ->addText($input['value'])
            ->end();
    }

    /**
     * @param string $type
     * @param array $input
     *
     * @return void
     */
    public function build(string $type, array $input)
    {
        $this->$type($input);
    }
}
