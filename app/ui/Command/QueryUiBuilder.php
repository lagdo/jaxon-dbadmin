<?php

namespace Lagdo\DbAdmin\Ui\Command;

use Jaxon\Script\Call\JxnCall;
use Lagdo\DbAdmin\Ajax\App\Db\Command\QueryHistory;
use Lagdo\DbAdmin\Ajax\App\Db\Command\QueryResults;
use Lagdo\DbAdmin\Ajax\App\Db\Table\Dql\Duration;
use Lagdo\DbAdmin\Translator;
use Lagdo\UiBuilder\BuilderInterface;

use function count;
use function Jaxon\je;
use function Jaxon\jo;
use function Jaxon\jq;
use function Jaxon\rq;

class QueryUiBuilder
{
    /**
     * @param Translator $trans
     * @param BuilderInterface $ui
     */
    public function __construct(protected Translator $trans, protected BuilderInterface $ui)
    {}

    /**
     * @param int $defaultLimit
     * @param JxnCall $rqQuery
     *
     * @return mixed
     */
    private function actions(int $defaultLimit, JxnCall $rqQuery): mixed
    {
        $formId = 'dbadmin-main-command-form';
        $sqlQuery = jo('jaxon.dbadmin')->getSqlQuery();

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
                            $this->ui->button(
                                    $this->ui->text($this->trans->lang('Execute'))
                                )
                                ->fullWidth()->primary()
                                ->jxnClick($rqQuery->exec($sqlQuery, je($formId)->rd()->form()))
                        )
                        ->width(8),
                        $this->ui->formCol()
                            ->width(4)
                            ->jxnBind(rq(Duration::class))
                    )
                )
                ->width(4)
            )
        )->horizontal(true)->wrapped(true)->setId($formId);
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
                ->jxnBind(rq(QueryResults::class)),
            $this->ui->col()
                ->width(12)
                ->jxnBind(rq(QueryHistory::class))
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
                            ->style('bordered')->setStyle('margin-top:2px')
                        ),
                    )
                    ->width(12)
                )
            )
        );
    }

    /**
     * @param array $commands
     *
     * @return string
     */
    public function history(array $commands): string
    {
        if (count($commands) === 0) {
            return '';
        }

        $commandId = jq()->attr('data-command-id');
        $btnEditHandler = jo('jaxon.dbadmin.history')->editSqlQuery($commandId);
        $btnInsertHandler = jo('jaxon.dbadmin.history')->insertSqlQuery($commandId);
        return $this->ui->build(
            $this->ui->panel(
                $this->ui->panelHeader($this->trans->lang('History')),
                $this->ui->panelBody(
                    $this->ui->each($commands, fn($query, $id) =>
                        $this->ui->row(
                            $this->ui->col($query)
                                ->setId("dbadmin-history-command-$id")
                                ->width(11),
                            $this->ui->col(
                                $this->ui->buttonGroup(
                                    $this->ui->button($this->trans->lang('Edit'))
                                        ->primary()
                                        ->setClass('dbadmin-history-query-edit')
                                        ->setDataCommandId($id),
                                    $this->ui->dropdownItem()->style('primary'),
                                    $this->ui->dropdownMenu(
                                        $this->ui->dropdownMenuItem($this->trans->lang('Insert'))
                                            ->setDataCommandId($id)
                                            ->setClass('dbadmin-history-query-insert')
                                    )
                                )
                            )->width(1)
                        )
                    )
                )
                ->setStyle('padding:5px 15px')
                ->jxnEvent([
                    ['.dbadmin-history-query-edit', 'click', $btnEditHandler],
                    ['.dbadmin-history-query-insert', 'click', $btnInsertHandler],
                ])
            ),
        );
    }
}
