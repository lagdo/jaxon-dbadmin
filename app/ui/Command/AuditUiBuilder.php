<?php

namespace Lagdo\DbAdmin\Ui\Command;

use Lagdo\DbAdmin\Ajax\Admin\Db\Command\Query;
use Lagdo\DbAdmin\Db\Translator;
use Lagdo\DbAdmin\Ui\TabApp;
use Lagdo\UiBuilder\BuilderInterface;

use function count;
use function Jaxon\cl;
use function Jaxon\form;
use function Jaxon\jo;
use function Jaxon\jq;
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
     */
    public function __construct(protected Translator $trans, protected BuilderInterface $ui)
    {}

    /**
     * @return mixed
     */
    private function historyButtons(): mixed
    {
        return $this->ui->buttonGroup(
            $this->ui->button($this->trans->lang('Copy'))
                ->primary()
                ->setClass('dbadmin-history-query-copy'),
            $this->ui->dropdownItem()->look('primary'),
            $this->ui->dropdownMenu(
                $this->ui->dropdownMenuItem($this->trans->lang('Insert'))
                    ->setClass('dbadmin-history-query-insert')
            )
        );
    }

    /**
     * @param array $queries
     *
     * @return string
     */
    public function history(array $queries): string
    {
        $jsThis = jq();
        $prefix = TabApp::id('dbadmin-history-query-');
        $btnCopyHandler = jo('jaxon.dbadmin.history')->copyQueryText($jsThis, $prefix);
        $btnInsertHandler = jo('jaxon.dbadmin.history')->insertQuerytext($jsThis, $prefix);
        return $this->ui->build(
            // $this->ui->row(
            //     $this->ui->col(
            //             $this->ui->h4($this->trans->lang('History'))
            //                 ->setStyle('font-size:16px; font-weight:600;')
            //         )->width(12)
            // ),
            $this->ui->row(
                $this->ui->col(
                    $this->ui->table(
                        $this->ui->tbody(
                            $this->ui->each($queries, fn($query, $id) =>
                                $this->ui->tr(
                                    $this->ui->td(
                                        $this->ui->div("[{$query['driver']}]")
                                            ->setStyle('font-size:14px; font-style:italic;'),
                                        $this->ui->div($query['query'])
                                            ->setId("{$prefix}{$id}")
                                    ),
                                    $this->ui->td($this->historyButtons())
                                        ->setDataQueryId($id)
                                        ->setStyle('width:50px;')
                                )
                            )
                        )->setStyle('padding:5px 15px')
                            ->jxnEvent([
                                ['.dbadmin-history-query-copy', 'click', $btnCopyHandler],
                                ['.dbadmin-history-query-insert', 'click', $btnInsertHandler],
                            ])
                    )->look('bordered'),
                )->width(12)
                    ->setClass('jaxon-dbadmin-sql-query-wrapper')
            ),
        );
    }

    /**
     * @return string
     */
    public function favorite(): string
    {
        return $this->ui->build(
            $this->ui->row(
                // $this->ui->col(
                //     $this->ui->h4($this->trans->lang('Favorites'))
                //         ->setStyle('font-size:16px; font-weight:600;'))
                //     ->width(3),
                $this->ui->col(
                    $this->ui->nav()
                        ->jxnPagination(cl(Query\FavoritePage::class))
                        ->setStyle('float:right;'))
                    ->width(9)
            ),
            $this->ui->row(
                $this->ui->col(
                    $this->ui->div()
                        ->tbnBindApp(rq(Query\FavoritePage::class))
                )->width(12),
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
            $this->ui->dropdownItem()->look('primary'),
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
    public function favorites(array $queries): string
    {
        if (count($queries) === 0) {
            return '';
        }

        $jsThis = jq();
        $prefix = TabApp::id('dbadmin-favorite-query-');
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
                    $this->ui->tbody(
                        $this->ui->each($queries, fn($query, $id) =>
                            $this->ui->tr(
                                $this->ui->td(
                                    $this->ui->div("[{$query['driver']}] {$query['title']}")
                                        ->setStyle('font-size:14px; font-style:italic;'),
                                    $this->ui->div($query['query'])
                                        ->setId("{$prefix}{$id}")
                                ),
                                $this->ui->td($this->favoriteButtons())
                                    ->setDataQueryId($id)
                                    ->setStyle('width:50px;')
                            )
                        )
                    )->jxnEvent([
                        ['.dbadmin-favorite-query-copy', 'click', $btnCopyHandler],
                        ['.dbadmin-favorite-query-insert', 'click', $btnInsertHandler],
                        ['.dbadmin-favorite-query-edit', 'click', $btnEditHandler],
                        ['.dbadmin-favorite-query-delete', 'click', $btnDeleteHandler],
                    ])
                )->look('bordered')
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
            )->horizontal(false)
                ->wrapped(true)
                ->setId(TabApp::id($this->favoriteFormId))
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
            )->horizontal(false)
                ->wrapped(true)
                ->setId(TabApp::id($this->favoriteFormId))
        );
    }

    /**
     * @return mixed
     */
    public function favoriteFormValues(): mixed
    {
        return form(TabApp::id($this->favoriteFormId));
    }
}
