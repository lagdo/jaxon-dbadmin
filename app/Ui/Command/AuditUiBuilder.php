<?php

namespace Lagdo\DbAdmin\App\Ui\Command;

use Lagdo\DbAdmin\App\Ajax\Admin\Db\Command\Query;
use Lagdo\DbAdmin\Support\Translator;
use Lagdo\DbAdmin\App\Ui\Tab\Tab;
use Lagdo\UiBuilder\BuilderInterface;

use function count;
use function Jaxon\cl;
use function Jaxon\jo;
use function Jaxon\jq;
use function Jaxon\pm;
use function Jaxon\rq;

class AuditUiBuilder
{
    /**
     * @var string
     */
    private $favoriteFormId = 'dbadmin-query-favorite';

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
     * @return mixed
     */
    private function historyButtons(): mixed
    {
        return $this->ui->buttonGroup(
            $this->ui->button($this->trans->lang('Copy'))
                ->primary()
                ->setClass('dbadmin-history-query-copy'),
            $this->ui->dropdownButton()
                ->primary(),
            $this->ui->dropdownMenu(
                $this->ui->dropdownMenuItem($this->trans->lang('Insert'))
                    ->setClass('dbadmin-history-query-insert')
            )
        );
    }

    /**
     * @param bool $favoriteEnabled
     *
     * @return string
     */
    public function history(bool $favoriteEnabled): string
    {
        return $this->ui->build(
            $this->ui->div(
                $this->ui->div(
                    $this->trans->lang('History')
                )->setStyle('width:200px; padding:10px; font-weight: 600;'),
                $this->ui->div(
                    $this->ui->nav()
                        ->jxnPagination(cl(Query\HistoryPage::class))
                )->setStyle('flex: 1'),
                $this->ui->div(
                    $this->ui->when($favoriteEnabled, fn() =>
                        $this->ui->button($this->trans->lang('Favorites'))
                            ->primary()
                            ->setStyle('float:right;')
                            ->jxnClick(rq(Query\Favorite::class)->render())
                    ),
                    $this->ui->button($this->trans->lang('Refresh'))
                        ->primary()
                        ->setStyle('float:right; margin-right: 5px;')
                        ->jxnClick(rq(Query\HistoryPage::class)->page())
                )
            )->setStyle('display:flex; flex-direction:row; align-items:flex-start;'),
            $this->ui->div(
                $this->ui->div()
                    ->tbnBindApp(rq(Query\HistoryPage::class))
            )
        );
    }

    /**
     * @param array $queries
     *
     * @return string
     */
    public function historyQueries(array $queries): string
    {
        $jsThis = jq();
        $prefix = $this->tab()->app()->id('dbadmin-history-query-');
        $btnCopyHandler = jo('jaxon.dbadmin.history')->copyQueryText($jsThis, $prefix);
        $btnInsertHandler = jo('jaxon.dbadmin.history')->insertQuerytext($jsThis, $prefix);

        return $this->ui->build(
            $this->ui->div(
                $this->ui->table(
                    $this->ui->tableBody(
                        $this->ui->each($queries, fn($query, $id) =>
                            $this->ui->tableRow(
                                $this->ui->tableDataCell($this->historyButtons())
                                    ->setDataQueryId($id)
                                    ->setStyle('width:60px;'),
                                $this->ui->tableDataCell(
                                    $this->ui->div("[{$query['driver']}]")
                                        ->setStyle('font-size:14px; font-style:italic;'),
                                    $this->ui->div($query['query'])
                                        ->setId("{$prefix}{$id}")
                                )
                            )
                        )
                    )->setStyle('padding:5px 15px')
                        ->jxnEvent([
                            ['.dbadmin-history-query-copy', 'click', $btnCopyHandler],
                            ['.dbadmin-history-query-insert', 'click', $btnInsertHandler],
                        ])
                )->border()
                    ->setClass('no-header')
            )->setClass('jaxon-dbadmin-sql-query-wrapper')
        );
    }

    /**
     * @param bool $historyEnabled
     *
     * @return string
     */
    public function favorite(bool $historyEnabled): string
    {
        return $this->ui->build(
            $this->ui->div(
                $this->ui->div(
                    $this->trans->lang('Favorites')
                )->setStyle('width:200px; padding:10px; font-weight: 600;'),
                $this->ui->div(
                    $this->ui->nav()
                        ->jxnPagination(cl(Query\FavoritePage::class))
                )->setStyle('flex: 1'),
                $this->ui->div(
                    $this->ui->when($historyEnabled, fn() =>
                        $this->ui->button($this->trans->lang('History'))
                            ->primary()
                            ->setStyle('float:right;')
                            ->jxnClick(rq(Query\History::class)->render()),
                    ),
                    $this->ui->button($this->trans->lang('Refresh'))
                        ->primary()
                        ->setStyle('float:right; margin-right: 5px;')
                        ->jxnClick(rq(Query\FavoritePage::class)->page())
                )
            )->setStyle('display:flex; flex-direction:row; align-items:flex-start;'),
            $this->ui->div(
                $this->ui->div()
                    ->tbnBindApp(rq(Query\FavoritePage::class))
            )
        );
    }

    /**
     * @return mixed
     */
    private function favoriteButtons(): mixed
    {
        return $this->ui->buttonGroup(
            $this->ui->button($this->trans->lang('Copy'))
                ->primary()
                ->setClass('dbadmin-favorite-query-copy'),
            $this->ui->dropdownButton()
                ->primary(),
            $this->ui->dropdownMenu(
                $this->ui->dropdownMenuItem($this->trans->lang('Insert'))
                    ->setClass('dbadmin-favorite-query-insert'),
                $this->ui->dropdownMenuItem($this->trans->lang('Edit'))
                    ->setClass('dbadmin-favorite-query-edit'),
                $this->ui->dropdownMenuItem($this->trans->lang('Delete'))
                    ->setClass('dbadmin-favorite-query-delete')
            )
        );
    }

    /**
     * @param array $queries
     *
     * @return string
     */
    public function favoriteQueries(array $queries): string
    {
        if (count($queries) === 0) {
            return '';
        }

        $jsThis = jq();
        $prefix = $this->tab()->app()->id('dbadmin-favorite-query-');
        $queryId = jo('jaxon.dbadmin.favorite')->getQueryId($jsThis);
        $sqlQuery = jo('jaxon.dbadmin.favorite')->getQueryText($jsThis, $prefix);
        $btnCopyHandler = jo('jaxon.dbadmin.favorite')->copyQueryText($jsThis, $prefix);
        $btnInsertHandler = jo('jaxon.dbadmin.favorite')->insertQuerytext($jsThis, $prefix);
        $btnEditHandler = rq(Query\FavoriteFunc::class)->edit($queryId, $sqlQuery);
        $btnDeleteHandler = rq(Query\FavoriteFunc::class)->delete($queryId)
            ->confirm($this->trans->lang('Delete this query from the favorites?'));

        return $this->ui->build(
            $this->ui->div(
                $this->ui->table(
                    $this->ui->tableBody(
                        $this->ui->each($queries, fn($query, $id) =>
                            $this->ui->tableRow(
                                $this->ui->tableDataCell($this->favoriteButtons())
                                    ->setDataQueryId($id)
                                    ->setStyle('width:60px;'),
                                $this->ui->tableDataCell(
                                    $this->ui->div("[{$query['driver']}] {$query['title']}")
                                        ->setStyle('font-size:14px; font-style:italic;'),
                                    $this->ui->div($query['query'])
                                        ->setId("{$prefix}{$id}")
                                )
                            )
                        )
                    )->jxnEvent([
                        ['.dbadmin-favorite-query-copy', 'click', $btnCopyHandler],
                        ['.dbadmin-favorite-query-insert', 'click', $btnInsertHandler],
                        ['.dbadmin-favorite-query-edit', 'click', $btnEditHandler],
                        ['.dbadmin-favorite-query-delete', 'click', $btnDeleteHandler],
                    ])
                )->border()
                    ->setClass('no-header')
            )->setClass('jaxon-dbadmin-sql-query-wrapper')
        );
    }

    /**
     * @param string $query
     *
     * @return string
     */
    public function addFavoriteForm(string $query): string
    {
        return $this->ui->build(
            $this->ui->form(
                $this->ui->label($this->ui->text('Title'))
                    ->setFor('title'),
                $this->ui->input()
                    ->setType('text')
                    ->setName('title'),
                $this->ui->label($this->ui->text('Query'))
                    ->setFor('query'),
                $this->ui->textarea($query)
                    ->setName('query')
                    ->setClass('jaxon-dbadmin-sql-query-wrapper')
            )->wrapped()
                ->setId($this->tab()->app()->id($this->favoriteFormId))
        );
    }

    /**
     * @param array $query
     *
     * @return string
     */
    public function editFavoriteForm(array $query): string
    {
        return $this->ui->build(
            $this->ui->form(
                $this->ui->label($this->ui->text('Title'))
                    ->setFor('title'),
                $this->ui->input()
                    ->setType('text')
                    ->setName('title')
                    ->setValue($query['title']),
                $this->ui->label($this->ui->text('Query'))
                    ->setFor('query'),
                $this->ui->textarea($query['query'])
                    ->setName('query')
                    ->setClass('jaxon-dbadmin-sql-query-wrapper')
            )->wrapped()
                ->setId($this->tab()->app()->id($this->favoriteFormId))
        );
    }

    /**
     * @return mixed
     */
    public function favoriteFormValues(): mixed
    {
        return pm()->form($this->tab()->app()->id($this->favoriteFormId));
    }
}
