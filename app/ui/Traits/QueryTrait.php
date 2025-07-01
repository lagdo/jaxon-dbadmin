<?php

namespace Lagdo\DbAdmin\Ui\Traits;

use Jaxon\Script\Call\JxnCall;
use Lagdo\DbAdmin\Ajax\App\Db\Command\QueryResults;
use Lagdo\UiBuilder\Jaxon\Builder;

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

        $htmlBuilder = Builder::new();
        $htmlBuilder
            ->col(12)->setId('dbadmin-command-details')
            ->end()
            ->col(12)
                ->row()
                    ->col(12)
                        ->panel('default')->setId('sql-command-editor')
                            ->panelBody()->setClass('sql-command-editor-panel')
                                ->formTextarea()
                                    ->setName('query')
                                    ->setId($queryId)
                                    ->setDataLanguage('sql')
                                    ->setRows('10')
                                    ->setSpellcheck('false')
                                    ->setWrap('on')
                                    ->addHtml($query)
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->col(12)
                ->form(true, true)->setId($formId)
                    ->formRow()
                        ->formCol(3)
                            ->inputGroup()
                                ->text()->addText($this->trans->lang('Limit rows'))
                                ->end()
                                ->formInput()
                                    ->setName('limit')
                                    ->setType('number')
                                    ->setValue($defaultLimit)
                                ->end()
                            ->end()
                        ->end()
                        ->formCol(3)
                            ->inputGroup()
                                ->text()->addText($this->trans->lang('Stop on error'))
                                ->end()
                                ->checkbox()->setName('error_stops')
                                ->end()
                            ->end()
                        ->end()
                        ->formCol(3)
                            ->inputGroup()
                                ->text()->addText($this->trans->lang('Show only errors'))
                                ->end()
                                ->checkbox()->setName('only_errors')
                                ->end()
                            ->end()
                        ->end()
                        ->formCol(2)
                            ->button()->btnFullWidth()->btnPrimary()
                                ->jxnClick($rqQuery->exec(jo('jaxon.dbadmin')->getSqlQuery(), pm()->form($formId))/*->when(pm()->input($queryId))*/)
                                ->addText($this->trans->lang('Execute'))
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->col(12)->setId('dbadmin-command-history')
            ->end()
            ->col(12)->jxnBind(rq(QueryResults::class))
            ->end();
        return $htmlBuilder->build();
    }

    /**
     * @param array $results
     *
     * @return string
     */
    public function queryResults(array $results): string
    {
        $htmlBuilder = Builder::new();
        $htmlBuilder;
        foreach ($results as $result) {
            $htmlBuilder
                ->row()
                    ->col(12);
            if (count($result['errors']) > 0) {
                $htmlBuilder
                        ->panel('danger')
                            ->panelHeader()->addText($result['query'])
                            ->end()
                            ->panelBody()->setStyle('padding:5px 15px');
                foreach($result['errors'] as $error) {
                    $htmlBuilder
                                ->addHtml($error);
                }
                $htmlBuilder
                            ->end()
                        ->end();
            }
            if (count($result['messages']) > 0) {
                $htmlBuilder
                        ->panel('info')
                            ->panelHeader()->addText($result['query'])
                            ->end()
                            ->panelBody()->setStyle('padding:5px 15px');
                foreach($result['messages'] as $message) {
                    $htmlBuilder
                                ->addHtml($message);
                }
                $htmlBuilder
                            ->end()
                        ->end();
            }
            if (isset($result['select'])) {
                $htmlBuilder
                        ->table(true, 'bordered')->setStyle('margin-top:2px')
                            ->thead()
                                ->tr();
                foreach ($result['select']['headers'] as $header) {
                    $htmlBuilder
                                    ->th()->addHtml($header)
                                    ->end();
                }
                $htmlBuilder
                                ->end()
                            ->end()
                            ->tbody();
                foreach ($result['select']['details'] as $details) {
                    $htmlBuilder
                                ->tr();
                    foreach ($details as $detail) {
                        $htmlBuilder
                                    ->td()->addHtml($detail)
                                    ->end();
                    }
                   $htmlBuilder
                                ->end();
                }
                $htmlBuilder
                            ->end()
                        ->end();
            }
            $htmlBuilder
                    ->end()
                ->end();
        }
        return $htmlBuilder->build();
    }
}
