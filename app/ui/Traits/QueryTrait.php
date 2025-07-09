<?php

namespace Lagdo\DbAdmin\Ui\Traits;

use Jaxon\Script\Call\JxnCall;
use Lagdo\DbAdmin\Ajax\App\Db\Command\QueryResults;
use Lagdo\UiBuilder\BuilderInterface;

use function count;
use function Jaxon\jo;
use function Jaxon\rq;
use function Jaxon\pm;

trait QueryTrait
{
    /**
     * @return BuilderInterface
     */
    abstract protected function builder(): BuilderInterface;

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

        $html = $this->builder();
        return $html->build(
            $html->col()->width(12)->setId('dbadmin-command-details'),
            $html->col(
                $html->row(
                    $html->col(
                        $html->panel(
                            $html->panelBody(
                                $html->formTextarea()
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
            $html->col(
                $html->form(
                    $html->formRow(
                        $html->formCol(
                            $html->inputGroup(
                                $html->text()
                                    ->addText($this->trans->lang('Limit rows')),
                                $html->formInput()
                                    ->setName('limit')
                                    ->setType('number')
                                    ->setValue($defaultLimit)
                            )
                        )
                        ->width(3),
                        $html->formCol(
                            $html->inputGroup(
                                $html->text()
                                    ->addText($this->trans->lang('Stop on error')),
                                $html->checkbox()
                                    ->setName('error_stops')
                            )
                        )
                        ->width(3),
                        $html->formCol(
                            $html->inputGroup(
                                $html->text()
                                    ->addText($this->trans->lang('Show only errors')),
                                $html->checkbox()
                                    ->setName('only_errors')
                            )
                        )
                        ->width(3),
                        $html->formCol(
                            $html->button()
                                ->fullWidth()->primary()
                                ->jxnClick($rqQuery->exec(jo('jaxon.dbadmin')->getSqlQuery(), pm()->form($formId))/*->when(pm()->input($queryId))*/)
                                ->addText($this->trans->lang('Execute'))
                        )
                        ->width(2)
                    )
                )->horizontal(true)->wrapped(true)->setId($formId)
            )
                ->width(12),
            $html->col()
                ->width(12)
                ->setId('dbadmin-command-history'),
            $html->col()
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
        $html = $this->builder();
        return $html->build(
            $html->each($results, fn($result) =>
                $html->row(
                    $html->col(
                        $html->when(count($result['errors']) > 0, fn() =>
                            $html->panel(
                                $html->panelHeader()->addText($result['query']),
                                $html->panelBody(
                                    $html->each($result['errors'], fn($error) =>
                                        $html->span($error)
                                    )
                                )
                                ->setStyle('padding:5px 15px')
                            )
                            ->style('danger')
                        ),
                        $html->when(count($result['messages']) > 0, fn() =>
                            $html->panel(
                                $html->panelHeader()->addText($result['query']),
                                $html->panelBody(
                                    $html->each($result['messages'], fn($message) =>
                                        $html->span($message)
                                    )
                                )
                                ->setStyle('padding:5px 15px')
                            )
                            ->style('danger')
                        ),
                        $html->when(isset($result['select']), fn() =>
                            $html->table(
                                $html->thead(
                                    $html->tr(
                                        $html->each($result['select']['headers'], fn($header) =>
                                            $html->th()->addHtml($header)
                                        )
                                    )
                                ),
                                $html->tbody(
                                    $html->each($result['select']['details'], fn($details) =>
                                        $html->tr(
                                            $html->each($details, fn($detail) =>
                                                $html->td()->addHtml($detail)
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
