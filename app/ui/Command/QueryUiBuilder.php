<?php

namespace Lagdo\DbAdmin\Ui\Command;

use Jaxon\Script\Call\JxnCall;
use Lagdo\DbAdmin\Ajax\App\Db\Command\Query;
use Lagdo\DbAdmin\Ajax\App\Db\Table\Dql\Duration;
use Lagdo\DbAdmin\Translator;
use Lagdo\UiBuilder\BuilderInterface;

use function count;
use function Jaxon\je;
use function Jaxon\jo;
use function Jaxon\rq;

class QueryUiBuilder
{
    /**
     * @var string
     */
    private $queryFormId = 'dbadmin-main-command-form';

    /**
     * @param Translator $trans
     * @param BuilderInterface $ui
     */
    public function __construct(protected Translator $trans, protected BuilderInterface $ui)
    {}

    /**
     * @param JxnCall $rqQuery
     *
     * @return mixed
     */
    private function queryButtons(JxnCall $rqQuery): mixed
    {
        $sqlQuery = jo('jaxon.dbadmin')->getSqlQuery();
        $queryValues = je($this->queryFormId)->rd()->form();

        return $this->ui->buttonGroup(
        $this->ui->button(
                $this->ui->text($this->trans->lang('Execute'))
            )
            ->primary()
            ->jxnClick($rqQuery->exec($sqlQuery, $queryValues)),
            $this->ui->button(
                $this->ui->text($this->trans->lang('Save'))
            )
            ->secondary()
            ->jxnClick(rq(Query\FavoriteFunc::class)->add($sqlQuery))
        )
        ->fullWidth();
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
            $this->ui->formRow(
                $this->ui->formCol(
                    $this->ui->inputGroup(
                        $this->ui->label(
                            $this->ui->text($this->trans->lang('Limit rows'))
                        ),
                        $this->ui->formInput()
                            ->setName('limit')
                            ->setType('number')
                            ->setValue($defaultLimit)
                    )
                )
                ->width(2),
                $this->ui->formCol(
                    $this->ui->inputGroup(
                        $this->ui->label(
                            $this->ui->text($this->trans->lang('Stop on error'))
                        ),
                        $this->ui->checkbox()
                            ->setName('error_stops')
                    )
                )
                ->width(3),
                $this->ui->formCol(
                    $this->ui->inputGroup(
                        $this->ui->label(
                            $this->ui->text($this->trans->lang('Show only errors'))
                        ),
                        $this->ui->checkbox()
                            ->setName('only_errors')
                    )
                )
                ->width(3),
                $this->ui->formCol(
                    $this->ui->formRow(
                        $this->ui->formCol(
                            $this->queryButtons($rqQuery)
                        )
                        ->width(8),
                        $this->ui->formCol()
                            ->width(4)
                            ->jxnBind(rq(Duration::class))
                    )
                )
                ->width(4)
            )
        )->horizontal(true)->wrapped(true)
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
            $this->ui->col()->width(12)->setId('dbadmin-command-details'),
            $this->ui->col(
                $this->ui->row(
                    $this->ui->col(
                        $this->ui->panel(
                            $this->ui->panelBody(
                                $this->ui->div()->setId($queryId)
                            )
                            ->setClass('sql-command-editor-panel')
                            ->setStyle('padding: 0 1px;')
                        )
                        ->style('default')
                        ->setStyle('padding: 5px;')
                    )
                    ->width(12)
                )
            )->width(12),
            $this->ui->col(
                $this->actions($defaultLimit, $rqQuery)
            )
                ->width(12),
            $this->ui->col()
                ->width(12)
                ->jxnBind(rq(Query\Results::class)),
            $this->ui->col($this->ui->jxnHtml(rq(Query\Queries::class)))
                ->width(12)
                ->jxnBind(rq(Query\Queries::class))
        );
    }

    /**
     * @param array $results
     *
     * @return string
     */
    public function results(array $results): string
    {
        return $this->ui->build(
            $this->ui->each($results, fn($result) =>
                $this->ui->row(
                    $this->ui->col(
                        $this->ui->when(count($result['errors']) > 0, fn() =>
                            $this->ui->panel(
                                $this->ui->panelHeader($this->ui->text($result['query'])),
                                $this->ui->panelBody(
                                    $this->ui->each($result['errors'], fn($error) =>
                                        $this->ui->span($error)
                                    )
                                )
                                ->setStyle('padding:5px 15px')
                            )
                            ->style('danger')
                        ),
                        $this->ui->when(count($result['messages']) > 0, fn() =>
                            $this->ui->panel(
                                $this->ui->panelHeader($this->ui->text($result['query'])),
                                $this->ui->panelBody(
                                    $this->ui->each($result['messages'], fn($message) =>
                                        $this->ui->span($message)
                                    )
                                )
                                ->setStyle('padding:5px 15px')
                            )
                            ->style('success')
                        ),
                        $this->ui->when(isset($result['select']), fn() =>
                            $this->ui->table(
                                $this->ui->thead(
                                    $this->ui->tr(
                                        $this->ui->each($result['select']['headers'], fn($header) =>
                                            $this->ui->th($this->ui->html($header))
                                        )
                                    )
                                ),
                                $this->ui->tbody(
                                    $this->ui->each($result['select']['details'], fn($details) =>
                                        $this->ui->tr(
                                            $this->ui->each($details, fn($detail) =>
                                                $this->ui->td($this->ui->html($detail))
                                            )
                                        )
                                    )
                                )
                            )
                            ->responsive(true)
                            ->style('bordered')
                            ->setStyle('margin-top:2px')
                        ),
                    )
                    ->width(12)
                )
            )
        );
    }

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
}
