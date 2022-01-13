<?php

namespace Lagdo\DbAdmin\Ui\Traits;

trait QueryTrait
{
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
            if (($result['select'])) {
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
