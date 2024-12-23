<?php

namespace Lagdo\DbAdmin\App\Ui\Traits;

use Lagdo\UiBuilder\Jaxon\Builder;

use function count;

trait QueryTrait
{
    /**
     * @param string $formId
     * @param string $queryId
     * @param string $btnId
     * @param string $query
     * @param int $defaultLimit
     * @param array $labels
     *
     * @return string
     */
    public function queryCommand(string $formId, string $queryId, string $btnId,
                                 string $query, int $defaultLimit, array $labels): string
    {
        $htmlBuilder = Builder::new();
        $htmlBuilder
            ->col(12)->setId('adminer-command-details')
            ->end()
            ->col(12)
                ->form(true, true)->setId($formId)
                    ->formRow()
                        ->panel('default')->setId('sql-command-editor')
                            ->panelBody()->setClass('sql-command-editor-panel')
                                ->formTextarea()->setName('query')
                                    ->setId($queryId)->setDataLanguage('sql')->setRows('10')
                                    ->setSpellcheck('false')->setWrap('on')->addHtml($query)
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->formRow()
                        ->formCol(3)
                            ->inputGroup()
                                ->text()->addText($labels['limit_rows'])
                                ->end()
                                ->formInput()->setName('limit')->setType('number')->setValue($defaultLimit)
                                ->end()
                            ->end()
                        ->end()
                        ->formCol(3)
                            ->inputGroup()
                                ->text()->addText($labels['error_stops'])
                                ->end()
                                ->checkbox()->setName('error_stops')
                                ->end()
                            ->end()
                        ->end()
                        ->formCol(3)
                            ->inputGroup()
                                ->text()->addText($labels['only_errors'])
                                ->end()
                                ->checkbox()->setName('only_errors')
                                ->end()
                            ->end()
                        ->end()
                        ->formCol(2)
                            ->button()->btnFullWidth()->btnPrimary()
                                ->setId($btnId)->addText($labels['execute'])
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->col(12)->setId('adminer-command-history')
            ->end()
            ->col(12)->setId('adminer-command-results')
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
                ->row();
            if (count($result['errors']) > 0) {
                $htmlBuilder
                    ->panel('danger')
                        ->panelHeader()->addText($result['query'])
                        ->end()
                        ->panelBody()->setStyle('padding:5px 15px');
                foreach($result['errors'] as $error) {
                    $htmlBuilder
                            ->addHtml('<p style="margin:0">' . $error . '</p>');
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
                            ->addHtml('<p style="margin:0">' . $message . '</p>');
                }
                $htmlBuilder
                        ->end()
                    ->end();
            }
            if (isset($result['select'])) {
                $htmlBuilder
                    ->table(true, 'bordered')
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
                ->end();
        }
        return $htmlBuilder->build();
    }
}
