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
     * @param array $htmlIds
     * @param array $contents
     * @param array $labels
     *
     * @return string
     */
    public function importPage(array $htmlIds, array $contents, array $labels): string
    {
        $this->htmlBuilder->clear()
            ->col(12)->setId('adminer-command-details')
            ->end()
            ->col(12)
                ->form(true, false)->setId($htmlIds['formId'])
                    ->row()
                        ->col(6)
                            ->formRow()
                                ->formCol(4)
                                    ->label($labels['file_upload'])
                                    ->end()
                                ->end();
        if (isset($contents['upload'])) {
            $this->htmlBuilder
                                ->formCol(8)->addHtml($contents['upload'])
                                ->end();
        } else {
            $this->htmlBuilder
                                ->formCol(8)->addHtml($contents['upload_disabled'])
                                ->end();
        }
        $this->htmlBuilder
                            ->end()
                            ->formRow();
        if (isset($contents['upload'])) {
            $this->htmlBuilder
                                ->formCol(12)
                                    ->inputGroup()->setId($htmlIds['sqlFilesDivId'])
                                        ->button($labels['select'] . '&hellip;', 'primary')->setId($htmlIds['sqlChooseBtnId'])
                                        ->end()
                                        ->input()->setType('file')->setName('sql_files[]')->setId($htmlIds['sqlFilesInputId'])
                                            ->setMultiple('multiple')->setStyle('display:none;')
                                        ->end()
                                        ->formInput()->setType('text')->setReadonly('readonly')
                                        ->end()
                                    ->end()
                                ->end();
        }
        $this->htmlBuilder
                            ->end()
                            ->formRow()
                                ->formCol(4)
                                    ->button($labels['execute'], 'primary', '', true)->setId($htmlIds['sqlFilesBtnId'])
                                    ->end()
                                ->end()
                            ->end()
                        ->end();
        if (isset($contents['path'])) {
            $this->htmlBuilder
                        ->col(6)
                            ->formRow()
                                ->formCol(4)
                                    ->label($labels['from_server'])
                                    ->end()
                                ->end()
                                ->formCol(8)->addText($labels['path'])
                                ->end()
                            ->end()
                            ->formRow()
                                ->formCol(12)
                                    ->formInput()->setType('text')->setValue($contents['path'])->setReadonly('readonly')
                                    ->end()
                                ->end()
                            ->end()
                            ->formRow()
                                ->formCol(4)
                                    ->button($labels['run_file'], 'primary', '', true)->setId($htmlIds['webFileBtnId'])
                                    ->end()
                                ->end()
                            ->end()
                        ->end();
        }
        $this->htmlBuilder
                    ->end()
                    ->row()
                        ->col(12)
                            ->formRow()
                                ->formCol(3)->addHtml('&nbsp;') // Actually an offset. TODO: a parameter for that.
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

    public function exportPage()
    {
        $this->htmlBuilder->clear()
            ->col(12)
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
