<?php

namespace Lagdo\DbAdmin\App\Ui\Select;

use Lagdo\DbAdmin\App\Ajax\Admin\Db\Command\Query\FavoriteFunc;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql\Duration;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql\GotoPage;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql\Options;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql\QueryText;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql\ResultSet;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql\Select;
use Lagdo\DbAdmin\Support\Translator;
use Lagdo\DbAdmin\App\Ui\Tab\Tab;
use Lagdo\UiBuilder\BuilderInterface;

use function Jaxon\cl;
use function Jaxon\input;
use function Jaxon\jo;
use function Jaxon\rq;
use function sprintf;

class SelectUiBuilder
{
    /**
     * @var string
     */
    private const QUERY_TEXT_CLASS = 'dbadmin-table-select-query';

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
     * @return string
     */
    public function queryTextId(): string
    {
        return $this->tab()->app()->id(self::QUERY_TEXT_CLASS);
    }

    /**
     * @param string $queryText
     *
     * @return string
     */
    public function queryText(string $queryText): string
    {
        return $this->ui->build(
            $this->ui->div($queryText)
                ->setClass(self::QUERY_TEXT_CLASS)
                ->setId($this->queryTextId())
        );
    }

    /**
     * @param float $duration
     *
     * @return string
     */
    public function duration(float $duration): string
    {
        return $this->ui->build(
            $this->ui->inputGroup(
                $this->ui->label(sprintf('%.4f&nbsp;s', $duration))
            )
        );
    }

    /**
     * @return string
     */
    public function formId(): string
    {
        return $this->tab()->app()->id('dbadmin-table-select-options-form');
    }

    /**
     * @param int $page
     *
     * @return string
     */
    public function gotoPageForm(int $page): string
    {
        $pageInputId = $this->tab()->app()->id('jaxon-dbadmin-resulset-goto-page');
        $pageNumber = input($pageInputId)->toInt();
        return $this->ui->build(
            $this->ui->form(
                $this->ui->inputGroup(
                    $this->ui->label(
                        $this->ui->text($this->trans->lang('Page'))
                    ),
                    $this->ui->input()
                        // ->setType('number')
                        ->setId($pageInputId)
                        ->setValue($page),
                    $this->ui->button()
                        ->outline()
                        ->secondary()
                        ->addIcon('ok')
                        ->jxnClick(rq(ResultSet::class)->page($pageNumber)
                            // In Js, NaN is not equal to itself.
                            ->ifteq($pageNumber, $pageNumber)
                            ->elseWarning('Invalid page number {1}', input($pageInputId)))
                )
            )
        );
    }

    /**
     * @param bool $canSaveQuery
     *
     * @return string
     */
    public function header(bool $canSaveQuery): string
    {
        return $this->ui->build(
            $this->ui->row(
                $this->ui->col(
                    $this->ui->form(
                        $this->ui->div(
                            $this->ui->row(
                                $this->ui->col()
                                    ->width(6)
                                    ->tbnBindApp(rq(Options\Fields::class)),
                                $this->ui->col()
                                    ->width(6)
                                    ->tbnBindApp(rq(Options\Values::class))
                            )
                        ),
                        $this->ui->row(
                            $this->ui->col(
                                $this->ui->panel(
                                    $this->ui->panelBody()
                                        ->setStyle('padding: 0 1px;')
                                        ->tbnBindApp(rq(QueryText::class))
                                )->look('default')
                                    ->setStyle('padding: 5px;')
                            )->width(12)
                        ),
                    )->wrapped(true)
                        ->setId($this->formId())
                )->width(12)
            ),
            $this->ui->row(
                $this->ui->col(
                    $this->ui->buttonGroup(
                        $this->ui->button($this->ui->text($this->trans->lang('Execute')))
                            ->fullWidth()->primary()
                            ->jxnClick(rq(ResultSet::class)->page()),
                        $this->ui->button($this->ui->text($this->trans->lang('Edit')))
                            ->outline()->secondary()->fullWidth()
                            ->jxnClick(rq(Select::class)->edit()),
                        $this->ui->when($canSaveQuery, fn() =>
                            $this->ui->button($this->ui->text($this->trans->lang('Save')))
                                ->outline()->secondary()->fullWidth()
                                ->jxnClick(rq(FavoriteFunc::class)->add(jo('jaxon.dbadmin')->getSelectQuery(), false))
                        )
                    )->fullWidth()
                )->width(4),
                $this->ui->col()
                    ->width(2)
                    ->tbnBindApp(rq(Duration::class))
            )->setStyle('margin-bottom: 0;')
        );
    }

    /**
     * @return string
     */
    public function content(): string
    {
        return $this->ui->build(
            $this->ui->row(
                $this->ui->col()
                    ->width(2)
                    ->tbnBindApp(rq(GotoPage::class)),
                $this->ui->col(
                    $this->ui->nav()
                        ->jxnPagination(cl(ResultSet::class))
                        ->setId($this->tab()->app()->id('jaxon-dbadmin-resulset-pagination'))
                )->width(10)
                    ->setStyle('overflow:hidden'),
            ),
            $this->ui->row(
                $this->ui->col()
                    ->width(12)
                    ->tbnBindApp(rq(ResultSet::class))
            )
        );
    }
}
