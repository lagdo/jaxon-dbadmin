<?php

namespace Lagdo\DbAdmin\Ui\Command;

use Jaxon\Script\Call\JxnCall;
use Lagdo\DbAdmin\Ajax\Admin\Db\Command\Query;
use Lagdo\DbAdmin\Ajax\Admin\Db\Table\Dql\Duration;
use Lagdo\DbAdmin\Db\Config\ServerConfig;
use Lagdo\DbAdmin\Db\Translator;
use Lagdo\UiBuilder\BuilderInterface;

use function Jaxon\form;
use function Jaxon\jo;
use function Jaxon\rq;

class QueryUiBuilder
{
    use QueryResultsTrait;

    /**
     * @var string
     */
    private $queryFormId = 'dbadmin-main-command-form';

    /**
     * @param Translator $trans
     * @param BuilderInterface $ui
     * @param ServerConfig $config
     */
    public function __construct(protected Translator $trans,
        protected BuilderInterface $ui, protected ServerConfig $config)
    {}

    /**
     * @param JxnCall $rqQuery
     *
     * @return mixed
     */
    private function queryButtons(JxnCall $rqQuery): mixed
    {
        $sqlQuery = jo('jaxon.dbadmin')->getSqlQuery();
        $queryValues = form($this->queryFormId);

        return $this->ui->buttonGroup(
        $this->ui->button(
                $this->ui->text($this->trans->lang('Execute'))
            )->primary()
                ->jxnClick($rqQuery->exec($sqlQuery, $queryValues)),
            $this->ui->button(
                $this->ui->text($this->trans->lang('Save'))
            )->secondary()
                ->jxnClick(rq(Query\FavoriteFunc::class)->add($sqlQuery))
        )->fullWidth();
    }

    /**
     * @param int $defaultLimit
     * @param JxnCall $rqQuery
     *
     * @return mixed
     */
    private function actions(int $defaultLimit, JxnCall $rqQuery): mixed
    {
        return $this->ui->form(
            $this->ui->row(
                $this->ui->col(
                    $this->ui->inputGroup(
                        $this->ui->label(
                            $this->ui->text($this->trans->lang('Limit rows'))
                        ),
                        $this->ui->input()
                            ->setName('limit')
                            ->setType('number')
                            ->setValue($defaultLimit)
                    )
                )->width(2),
                $this->ui->col(
                    $this->ui->inputGroup(
                        $this->ui->label(
                            $this->ui->text($this->trans->lang('Stop on error'))
                        ),
                        $this->ui->checkbox()
                            ->setName('error_stops')
                    )
                )->width(3),
                $this->ui->col(
                    $this->ui->inputGroup(
                        $this->ui->label(
                            $this->ui->text($this->trans->lang('Show only errors'))
                        ),
                        $this->ui->checkbox()
                            ->setName('only_errors')
                    )
                ) ->width(3),
                $this->ui->col(
                    $this->ui->row(
                        $this->ui->col(
                            $this->queryButtons($rqQuery)
                        )
                        ->width(8),
                        $this->ui->col()
                            ->width(4)
                            ->tbnBind(rq(Duration::class))
                    )
                )->width(4)
            )
        )->horizontal(true)
            ->wrapped(true)
            ->setId($this->queryFormId);
    }

    /**
     * @param string $queryId
     * @param JxnCall $rqQuery
     * @param int $defaultLimit
     *
     * @return string
     */
    public function command(string $queryId, JxnCall $rqQuery, int $defaultLimit): string
    {
        return $this->ui->build(
            $this->ui->tabNav(
                $this->ui->tabNavItem($this->trans->lang('Editor'))
                    ->target("tab-content-query-editor")
                    ->jxnOn('click', jo('jaxon.dbadmin')->refreshContent())
                    ->active(true),
                $this->ui->when($this->config->favoriteEnabled(), fn() =>
                    $this->ui->tabNavItem($this->trans->lang('History'))
                        ->target("tab-content-query-history")
                        ->active(false)
                ),
                $this->ui->when($this->config->historyEnabled(), fn() =>
                    $this->ui->tabNavItem($this->trans->lang('Favorites'))
                        ->target("tab-content-query-favorite")
                        ->active(false)
                )
            )->setStyle('margin-bottom: 5px;'),
            $this->ui->tabContent(
                $this->ui->tabContentItem(
                    $this->ui->row(
                        $this->ui->col()->width(12)->setId('dbadmin-command-details'),
                        $this->ui->col(
                            $this->ui->row(
                                $this->ui->col(
                                    $this->ui->panel(
                                        $this->ui->panelBody(
                                            $this->ui->div()->setId($queryId)
                                        )->setClass('sql-command-editor-panel')
                                            ->setStyle('padding: 0 1px;')
                                    )->look('default')
                                        ->setStyle('padding: 5px;')
                                )->width(12)
                            )
                        )->width(12),
                        $this->ui->col(
                            $this->actions($defaultLimit, $rqQuery)
                        )->width(12),
                        $this->ui->col()
                            ->width(12)
                            ->tbnBind(rq(Query\Results::class))
                    )
                )->setId("tab-content-query-editor")
                    ->active(true),
                $this->ui->when($this->config->favoriteEnabled(), fn() =>
                    $this->ui->tabContentItem()
                        ->tbnBind(rq(Query\History::class))
                        ->setId("tab-content-query-history")
                        ->active(false)),
                $this->ui->when($this->config->historyEnabled(), fn() =>
                    $this->ui->tabContentItem()
                        ->tbnBind(rq(Query\Favorite::class))
                        ->setId("tab-content-query-favorite")
                        ->active(false))
            )
        );
    }
}
