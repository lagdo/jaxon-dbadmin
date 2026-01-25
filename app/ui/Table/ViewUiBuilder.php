<?php

namespace Lagdo\DbAdmin\Ui\Table;

use Lagdo\DbAdmin\Db\Translator;
use Lagdo\DbAdmin\Ui\PageTrait;
use Lagdo\DbAdmin\Ui\Tab;
use Lagdo\UiBuilder\BuilderInterface;

class ViewUiBuilder
{
    use PageTrait;
    use TableTrait;

    /**
     * @var string
     */
    private const QUERY_FORM_CLASS = 'dbadmin-views-edit-view';

    /**
     * @param Translator $trans
     * @param BuilderInterface $ui
     */
    public function __construct(protected Translator $trans, protected BuilderInterface $ui)
    {}

    /**
     * @return string
     */
    public function queryFormId(): string
    {
        return Tab::id(self::QUERY_FORM_CLASS);
    }

    /**
     * @param bool $materializedView
     * @param array $view
     *
     * @return string
     */
    public function form(bool $materializedView, array $view = []): string
    {
        return $this->ui->build(
            $this->ui->col(
                $this->ui->form(
                    $this->ui->row(
                        $this->ui->col(
                            $this->ui->input()
                                ->setType('text')->setPlaceholder('Name')
                                ->setName('name')->setValue($view['name'] ?? '')
                        )->width(6),
                        $this->ui->when($materializedView, fn() =>
                            $this->ui->col(
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
                                )->setClass(self::QUERY_FORM_CLASS)
                                    ->setId($this->queryFormId())
                            )->setClass('sql-command-editor-panel')
                                ->setStyle('padding: 0 1px;')
                        )->look('default')
                            ->setStyle('padding: 5px;')
                    )->width(12)
                )
            )->width(12)
        );
    }
}
