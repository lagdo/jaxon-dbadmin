<?php

namespace Lagdo\DbAdmin\Ui\Traits;

use Jaxon\Script\Call\JxnCall;
use Lagdo\DbAdmin\Ajax\App\Db\Command\QueryResults;

use function count;
use function Jaxon\jo;
use function Jaxon\rq;
use function Jaxon\pm;

trait QueryTrait
{
    /**
     * @param string $query
     * @param int $defaultLimit
     *
     * @return string
     */
    public function queryCommand(string $query, int $defaultLimit, JxnCall $rqQuery): string
    {
        $formId = 'dbadmin-main-command-form';
        $queryId = 'dbadmin-main-command-query';

        return $this->html->build(
            $this->html->col()->width(12)->setId('dbadmin-command-details'),
            $this->html->col(
                $this->html->row(
                    $this->html->col(
                        $this->html->panel(
                            $this->html->panelBody(
                                $this->html->formTextarea()
                                    ->setName('query')
                                    ->setId($queryId)
                                    ->setDataLanguage('sql')
                                    ->setRows('10')
                                    ->setSpellcheck('false')
                                    ->setWrap('on')
                                    ->addHtml($query)
                            )
                            ->setClass('sql-command-editor-panel')
                        )
                        ->style('default')
                        ->setId('sql-command-editor')
                    )
                    ->width(12)
                )
            )->width(12),
            $this->html->col(
                $this->html->form(
                    $this->html->formRow(
                        $this->html->formCol(
                            $this->html->inputGroup(
                                $this->html->text()
                                    ->addText($this->trans->lang('Limit rows')),
                                $this->html->formInput()
                                    ->setName('limit')
                                    ->setType('number')
                                    ->setValue($defaultLimit)
                            )
                        )
                        ->width(3),
                        $this->html->formCol(
                            $this->html->inputGroup(
                                $this->html->text()
                                    ->addText($this->trans->lang('Stop on error')),
                                $this->html->checkbox()
                                    ->setName('error_stops')
                            )
                        )
                        ->width(3),
                        $this->html->formCol(
                            $this->html->inputGroup(
                                $this->html->text()
                                    ->addText($this->trans->lang('Show only errors')),
                                $this->html->checkbox()
                                    ->setName('only_errors')
                            )
                        )
                        ->width(3),
                        $this->html->formCol(
                            $this->html->button()
                                ->fullWidth()->primary()
                                ->jxnClick($rqQuery->exec(jo('jaxon.dbadmin')->getSqlQuery(), pm()->form($formId))/*->when(pm()->input($queryId))*/)
                                ->addText($this->trans->lang('Execute'))
                        )
                        ->width(2)
                    )
                )->horizontal(true)->wrapped(true)->setId($formId)
            )
                ->width(12),
            $this->html->col()
                ->width(12)
                ->setId('dbadmin-command-history'),
            $this->html->col()
                ->width(12)
                ->jxnBind(rq(QueryResults::class))
        );
    }

    /**
     * @param array $results
     *
     * @return string
     */
    public function queryResults(array $results): string
    {
        return $this->html->build(
            $this->html->each($results, fn($result) =>
                $this->html->row(
                    $this->html->col(
                        $this->html->when(count($result['errors']) > 0, fn() =>
                            $this->html->panel(
                                $this->html->panelHeader()->addText($result['query']),
                                $this->html->panelBody(
                                    $this->html->each($result['errors'], fn($error) =>
                                        $this->html->span($error)
                                    )
                                )
                                ->setStyle('padding:5px 15px')
                            )
                            ->style('danger')
                        ),
                        $this->html->when(count($result['messages']) > 0, fn() =>
                            $this->html->panel(
                                $this->html->panelHeader()->addText($result['query']),
                                $this->html->panelBody(
                                    $this->html->each($result['messages'], fn($message) =>
                                        $this->html->span($message)
                                    )
                                )
                                ->setStyle('padding:5px 15px')
                            )
                            ->style('danger')
                        ),
                        $this->html->when(isset($result['select']), fn() =>
                            $this->html->table(
                                $this->html->thead(
                                    $this->html->tr(
                                        $this->html->each($result['select']['headers'], fn($header) =>
                                            $this->html->th()->addHtml($header)
                                        )
                                    )
                                ),
                                $this->html->tbody(
                                    $this->html->each($result['select']['details'], fn($details) =>
                                        $this->html->tr(
                                            $this->html->each($details, fn($detail) =>
                                                $this->html->td()->addHtml($detail)
                                            )
                                        )
                                    )
                                ),
                            )
                            ->responsive(true)->style('bordered')->setStyle('margin-top:2px')
                        ),
                    )
                    ->width(12)
                )
            )
        );
    }
}
