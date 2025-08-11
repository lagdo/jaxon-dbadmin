<?php

namespace Lagdo\DbAdmin\Ui\Table;

use Lagdo\DbAdmin\Translator;
use Lagdo\DbAdmin\Ui\PageTrait;
use Lagdo\UiBuilder\BuilderInterface;

class ViewUiBuilder
{
    use PageTrait;
    use TableTrait;

    /**
     * @param Translator $trans
     * @param BuilderInterface $ui
     */
    public function __construct(protected Translator $trans, protected BuilderInterface $ui)
    {}

    /**
     * @return BuilderInterface
     */
    protected function builder(): BuilderInterface
    {
        return $this->ui;
    }

    /**
     * @param string $formId
     * @param bool $materializedView
     * @param array $view
     *
     * @return string
     */
    public function form(string $formId, bool $materializedView, array $view = []): string
    {
        return $this->ui->build(
            $this->ui->form(
                $this->ui->formRow(
                    $this->ui->formLabel($this->ui->text('Name'))
                        ->setFor('name')
                ),
                $this->ui->formRow(
                    $this->ui->formInput()
                        ->setType('text')->setName('name')
                        ->setPlaceholder('Name')->setValue($view['name'] ?? '')
                ),
                $this->ui->formRow(
                    $this->ui->formLabel($this->ui->text('SQL query'))
                        ->setFor('select')
                ),
                $this->ui->formRow(
                    $this->ui->formTextarea($this->ui->text($view['select'] ?? ''))
                        ->setRows('10')->setName('select')
                        ->setSpellcheck('false')->setWrap('on')
                ),
                $this->ui->when($materializedView, fn() =>
                    $this->ui->list(
                        $this->ui->formRow(
                            $this->ui->formLabel($this->ui->text('Materialized'))
                                ->setFor('materialized')
                        ),
                        $this->ui->formRow(
                            $this->ui->checkbox()
                                ->checked($view['materialized'] ?? false)
                                ->setName('materialized')
                        )
                    )
                )
            )
            ->wrapped()->setId($formId)
        );
    }
}
