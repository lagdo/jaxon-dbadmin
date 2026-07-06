<?php

namespace Lagdo\DbAdmin\App\Ui\Select;

use Lagdo\DbAdmin\App\Ajax\Admin\Db\Command\Query\FavoriteFunc;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\QueryBuilder;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql\Duration;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql\Foreigns;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql\GotoPage;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql\QueryText;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql\ResultSet;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\Dql\Select;
use Lagdo\DbAdmin\App\Ui\PageTrait;
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
    use PageTrait;

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
     * @param bool $checked
     *
     * @return string
     */
    public function toggleForeigns(bool $checked): string
    {
        return $this->ui->build(
            $this->ui->switch()
                ->checked($checked)
                ->label($this->trans->lang('Foreigns'))
                ->jxnClick(rq(QueryBuilder\Fields\Foreigns::class)->toggle())
        );
    }

    /**
     * @param bool $canSaveQuery
     * @param bool $canGoBack
     *
     * @return string
     */
    public function content(bool $canSaveQuery, bool $canGoBack = false): string
    {
        $queryActions = [[
            'label' => $this->trans->lang('Execute'),
            'handler' => rq(ResultSet::class)->page(1),
        ], [
            'label' => $this->trans->lang('Edit'),
            'handler' => rq(Select::class)->edit(),
        ]];
        if ($canSaveQuery) {
            $queryActions[] = [
                'label' => $this->trans->lang('Save'),
                'handler' => rq(FavoriteFunc::class)
                    ->add(jo('jaxon.dbadmin')->getSelectQuery(), false),
            ];
        }

        return $this->ui->build(
            $this->ui->form(
                $this->ui->row(
                    $this->ui->col()
                        ->width(6)
                        ->tbnBindApp(rq(QueryBuilder\Fields::class)),
                    $this->ui->col()
                        ->width(6)
                        ->tbnBindApp(rq(QueryBuilder\Values::class))
                ),
                $this->ui->row(
                    $this->ui->col(
                        $this->ui->card(
                            $this->ui->cardBody()
                                ->setStyle('padding: 0 1px;')
                                ->tbnBindApp(rq(QueryText::class))
                        )->setStyle('padding: 5px;')
                    )->width(12)
                ),
            )->wrapped()
                ->setId($this->formId()),
            $this->ui->row(
                $this->ui->col(
                    $this->_buttonMenu($queryActions)
                )->width(2),
                $this->ui->col()
                    ->width(1)
                    ->tbnBindApp(rq(Duration::class)),
                $this->ui->col()
                    ->width(2)
                    ->tbnBindApp(rq(Foreigns::class))
                    ->setStyle('display: flex; align-items: center; justify-content: flex-end;'),
                $this->ui->col()
                    ->width(2)
                    ->tbnBindApp(rq(GotoPage::class)),
                $this->ui->col(
                    $this->ui->nav()
                        ->jxnPagination(cl(ResultSet::class))
                        ->setId($this->tab()->app()->id('jaxon-dbadmin-resulset-pagination'))
                )->width(4)
                    ->setStyle('overflow:hidden'),
                $this->ui->col(
                    $this->ui->when($canGoBack, fn() =>
                        $this->ui->div(
                            $this->ui->button(
                                $this->ui->html('<i class="fa fa-arrow-left"></i>&nbsp;'),
                                $this->ui->text($this->trans->lang('Back'))
                            )->primary()
                                ->jxnClick(rq(Select::class)->back())
                        )->setStyle('float:right;')
                    )
                )->width(1)
            ),
            $this->ui->row(
                $this->ui->col()
                    ->width(12)
                    ->tbnBindApp(rq(ResultSet::class))
            )->setStyle('margin-top: 20px;')
        );
    }
}
