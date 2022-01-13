<?php

namespace Lagdo\DbAdmin\Ui\Traits;

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
    public function queryCommand(string $formId, string $queryId, string $btnId, string $query, int $defaultLimit, array $labels): string
    {
        $this->htmlBuilder->clear()
            ->col(12)->setId('adminer-command-details')
            ->end()
            ->col(12)
                ->form(true)->setId($formId)
                    ->formRow()
                        ->panel('default')->setId('sql-command-editor')
                            ->panelBody()
                                ->formTextarea()->setName('query')->setId($queryId)->setDataLanguage('sql')->setRows('10')
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
                            ->button($labels['execute'], 'primary', '', true)->setId($btnId)
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->col(12)->setId('adminer-command-history')
            ->end()
            ->col(12)->setId('adminer-command-results')
            ->end();
        return $this->htmlBuilder->build();
    }

    /**
     * @param array $results
     *
     * @return string
     */
    public function queryResults(array $results): string
    {
        $this->htmlBuilder->clear();
        foreach ($results as $result) {
            $this->htmlBuilder
                ->row();
            if (count($result['errors']) > 0) {
                $this->htmlBuilder
                    ->panel('danger')
                        ->panelHeader()->addText($result['query'])
                        ->end()
                        ->panelBody()->setStyle('padding:5px 15px');
                foreach($result['errors'] as $error) {
                    $this->htmlBuilder
                            ->addHtml('<p style="margin:0">' . $error . '</p>');
                }
                $this->htmlBuilder
                        ->end()
                    ->end();
            }
            if (count($result['messages']) > 0) {
                $this->htmlBuilder
                    ->panel('info')
                        ->panelHeader()->addText($result['query'])
                        ->end()
                        ->panelBody()->setStyle('padding:5px 15px');
                foreach($result['messages'] as $message) {
                    $this->htmlBuilder
                            ->addHtml('<p style="margin:0">' . $message . '</p>');
                }
                $this->htmlBuilder
                        ->end()
                    ->end();
            }
            if (isset($result['select'])) {
                $this->htmlBuilder
                    ->table(true, 'bordered')
                        ->thead()
                            ->tr();
                foreach ($result['select']['headers'] as $header) {
                    $this->htmlBuilder
                                ->th()->addHtml($header)
                                ->end();
                }
                $this->htmlBuilder
                            ->end()
                        ->end()
                        ->tbody();
                foreach ($result['select']['details'] as $details) {
                    $this->htmlBuilder
                            ->tr();
                    foreach ($details as $detail) {
                        $this->htmlBuilder
                                ->td()->addHtml($detail)
                                ->end();
                    }
                   $this->htmlBuilder
                            ->end();
                }
                $this->htmlBuilder
                        ->end()
                    ->end();
            }
            $this->htmlBuilder
                ->end();
        }
        return $this->htmlBuilder->build();
    }
}
