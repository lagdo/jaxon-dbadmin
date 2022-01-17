<?php

namespace Lagdo\DbAdmin\Ui;

use Lagdo\DbAdmin\Db\Translator;
use Lagdo\DbAdmin\Ui\Builder\BuilderInterface;

class InputBuilder
{
    /**
     * @var BuilderInterface
     */
    protected $htmlBuilder;

    /**
     * @var Translator
     */
    protected $trans;

    /**
     * @param BuilderInterface $htmlBuilder
     * @param Translator $trans
     */
    public function __construct(BuilderInterface $htmlBuilder, Translator $trans)
    {
        $this->htmlBuilder = $htmlBuilder;
        $this->trans = $trans;
    }

    /**
     * @param array $input
     *
     * @return void
     */
    protected function bool(array $input)
    {
        $this->htmlBuilder
            ->div()->setClass('checkbox')
                ->input()->setName($input['attrs']['name'])->setValue('0')->setType('hidden')
                ->end()
                ->checkbox($input['attrs']['checked'])->setName($input['attrs']['name'])->setValue('1')
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
        $this->htmlBuilder
            ->div()->setClass('checkbox');
        foreach ($input['values'] as $value) {
            $name = $input['attrs']['name'] . '[' . $value['value'] . ']';
            $this->htmlBuilder
                ->label()->setFor($name)
                    ->checkbox($value['checked'])->setName($name)
                    ->end()
                    ->addText($value['text'])
                ->end();
        }
        $this->htmlBuilder
            ->end();
    }

    /**
     * @param array $input
     *
     * @return void
     */
    protected function file(array $input)
    {
        $this->htmlBuilder
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
        $this->htmlBuilder
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
        $this->htmlBuilder
            ->div()->setClass('radio');
        foreach ($input['values'] as $value) {
            $this->htmlBuilder
                ->label()
                    ->checkbox($value['checked'], $input['attrs'])->setValue($value['value'])
                    ->end()
                    ->addText($value['text'])
                ->end();
        }
        $this->htmlBuilder
            ->end();
    }

    /**
     * @param array $input
     *
     * @return void
     */
    protected function textarea(array $input)
    {
        $this->htmlBuilder
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
