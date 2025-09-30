<?php

namespace Lagdo\DbAdmin\Ui\Command;

use Lagdo\DbAdmin\Ajax\App\Db\Command\Query;
use Lagdo\DbAdmin\Translator;
use Lagdo\UiBuilder\BuilderInterface;

use function count;
use function Jaxon\je;
use function Jaxon\jo;
use function Jaxon\jq;
use function Jaxon\rq;

class LogUiBuilder
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
     * @return string
     */
    public function queries(): string
    {
        return $this->ui->build(
            $this->ui->div()
                ->jxnBind(rq(Query\Favorite::class)),
            $this->ui->div()
                ->jxnBind(rq(Query\History::class))
        );
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
            $this->ui->dropdownItem()->style('primary'),
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
        if (count($queries) === 0) {
            return '';
        }

        $btnCopyHandler = jo('jaxon.dbadmin.history')->copySqlQuery(jq());
        $btnInsertHandler = jo('jaxon.dbadmin.history')->insertSqlQuery(jq());
        return $this->ui->build(
            $this->ui->row(
                $this->ui->col(
                        $this->ui->h4($this->trans->lang('History'))
                            ->setStyle('font-size:16px;')
                    )
                    ->width(12)
            ),
            $this->ui->row(
                $this->ui->col(
                    $this->ui->table(
                        $this->ui->tbody(
                            $this->ui->each($queries, fn($query, $id) =>
                                $this->ui->tr(
                                    $this->ui->td($query)
                                        ->setId("dbadmin-history-query-$id"),
                                    $this->ui->td($this->historyButtons())
                                        ->setDataQueryId($id)
                                        ->setStyle('width:50px;')
                                )
                            )
                        )
                        ->setStyle('padding:5px 15px')
                        ->jxnEvent([
                            ['.dbadmin-history-query-copy', 'click', $btnCopyHandler],
                            ['.dbadmin-history-query-insert', 'click', $btnInsertHandler],
                        ])
                    )
                    ->responsive(true)->style('bordered'),
                )
                ->width(12)
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
                $this->ui->col(
                    $this->ui->h4($this->trans->lang('Favorites'))
                        ->setStyle('font-size:16px;'))
                    ->width(3),
                $this->ui->col(
                    $this->ui->nav()
                        ->jxnPagination(rq(Query\FavoritePage::class))
                        ->setStyle('float:right;'))
                    ->width(9)
            ),
            $this->ui->row(
                $this->ui->col(
                    $this->ui->div()
                        ->jxnBind(rq(Query\FavoritePage::class))
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
            $this->ui->dropdownItem()->style('primary'),
            $this->ui->dropdownMenu(
                $this->ui->dropdownMenuItem($this->trans->lang('Insert'))
                    ->setClass('dbadmin-favorite-query-insert'),
                $this->ui->dropdownMenuItem($this->trans->lang('Update'))
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

        $sqlQuery = jo('jaxon.dbadmin')->getSqlQuery();
        $queryId = jo('jaxon.dbadmin.favorite')->getQueryId(jq());
        $btnCopyHandler = jo('jaxon.dbadmin.favorite')->copySqlQuery(jq());
        $btnInsertHandler = jo('jaxon.dbadmin.favorite')->insertSqlQuery(jq());
        $btnEditHandler = rq(Query\FavoriteFunc::class)->edit($queryId, $sqlQuery);
        $btnDeleteHandler = rq(Query\FavoriteFunc::class)->delete($queryId)
            ->confirm($this->trans->lang('Delete this query from the favorites?'));
        return $this->ui->build(
            $this->ui->table(
                $this->ui->tbody(
                    $this->ui->each($queries, fn($query) =>
                        $this->ui->tr(
                            $this->ui->td(
                                $this->ui->div("[{$query['driver']}] {$query['title']}")
                                    ->setStyle('font-size:14px; font-style:italic;'),
                                $this->ui->div($query['query'])
                                    ->setId('dbadmin-favorite-query-' . $query['id'])
                            ),
                            $this->ui->td(
                                $this->favoriteButtons()
                            )
                                ->setDataQueryId($query['id'])
                                ->setStyle('width:50px;')
                        )
                    )
                )
                ->jxnEvent([
                    ['.dbadmin-favorite-query-copy', 'click', $btnCopyHandler],
                    ['.dbadmin-favorite-query-insert', 'click', $btnInsertHandler],
                    ['.dbadmin-favorite-query-edit', 'click', $btnEditHandler],
                    ['.dbadmin-favorite-query-delete', 'click', $btnDeleteHandler],
                ])
            )
            ->responsive(true)->style('bordered'),
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
                $this->ui->formLabel($this->ui->text('Title'))
                    ->setFor('title'),
                $this->ui->formInput()
                    ->setType('text')
                    ->setName('title'),
                $this->ui->formLabel($this->ui->text('Query'))
                    ->setFor('query'),
                $this->ui->formTextarea($query)
                    ->setName('query')
                    ->setStyle('min-height:200px;')
            )
            ->responsive(true)->horizontal(false)->wrapped(true)
            ->setId($this->favoriteFormId)
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
                $this->ui->formLabel($this->ui->text('Title'))
                    ->setFor('title'),
                $this->ui->formInput()
                    ->setType('text')
                    ->setName('title')
                    ->setValue($query['title']),
                $this->ui->formLabel($this->ui->text('Query'))
                    ->setFor('query'),
                $this->ui->formTextarea($query['query'])
                    ->setName('query')
                    ->setStyle('min-height:200px;')
            )
            ->responsive(true)->horizontal(false)->wrapped(true)
            ->setId($this->favoriteFormId)
        );
    }

    /**
     * @return mixed
     */
    public function favoriteFormValues(): mixed
    {
        return je($this->favoriteFormId)->rd()->form();
    }
}
