<?php

namespace Lagdo\DbAdmin\Ui\Traits;

trait DatabaseTrait
{
    /**
     * @param string $formId
     * @param bool $materializedView
     * @param array $view
     *
     * @return string
     */
    public function viewForm(string $formId, bool $materializedView, array $view = []): string
    {
        $this->htmlBuilder->clear()
            ->form(false)->setId($formId)
                ->formRow()
                    ->formLabel()->setFor('name')->addText('Name')
                    ->end()
                ->end()
                ->formRow()
                    ->formInput()->setType('text')->setName('name')->setPlaceholder('Name')
                        ->setValue($view['name'] ?? '')
                    ->end()
                ->end()
                ->formRow()
                    ->formLabel()->setFor('select')->addText('SQL query')
                    ->end()
                ->end()
                ->formRow()
                    ->formTextarea()->setRows('10')->setName('select')->setSpellcheck('false')->setWrap('on')
                        ->addText($view['select'] ?? '')
                    ->end()
                ->end();
        if ($materializedView) {
            $this->htmlBuilder
                ->formRow()
                    ->formLabel()->setFor('materialized')->addText('Materialized')
                    ->end()
                ->end()
                ->formRow()
                    ->checkbox($view['materialized'] ?? false)->setName('materialized')
                    ->end()
                ->end();
        }
        $this->htmlBuilder
            ->end();
        return $this->htmlBuilder->build();
    }
}
