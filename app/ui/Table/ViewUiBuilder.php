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
     * @param string $queryId
     * @param bool $materializedView
     * @param array $view
     *
     * @return string
     */
    public function form(string $queryId, bool $materializedView, array $view = []): string
    {
        return $this->ui->build(
            $this->ui->col(
                $this->ui->form(
                    $this->ui->formRow(
                        $this->ui->formCol(
                            $this->ui->formInput()
                                ->setType('text')->setPlaceholder('Name')
                                ->setName('name')->setValue($view['name'] ?? '')
                        )->width(6),
                        $this->ui->when($materializedView, fn() =>
                            $this->ui->formCol(
                                $this->ui->inputGroup(
                                    $this->ui->checkbox()
                                        ->checked($view['materialized'] ?? false)
                                        ->setName('materialized'),
                                    $this->ui->label($this->ui->text('Materialized'))
                                        ->setFor('materialized'),
                                ),
                            )->width(6),
                        )
                    )
                )
            )->width(12),
            $this->ui->col(
                $this->ui->row(
                    $this->ui->col(
                        $this->ui->panel(
                            $this->ui->panelBody(
                                $this->ui->div(
                                        $this->ui->html($view['select'] ?? '')
                                    )->setId($queryId)
                            )
                            ->setClass('sql-command-editor-panel')
                            ->setStyle('padding: 0 1px;')
                        )
                        ->style('default')
                        ->setStyle('padding: 5px;')
                    )
                    ->width(12)
                )
            )->width(12)
        );
    }
}
