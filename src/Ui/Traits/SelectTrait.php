<?php

namespace Lagdo\DbAdmin\Ui\Traits;

use Lagdo\DbAdmin\Ui\Builder\AbstractBuilder;

trait SelectTrait
{
    /**
     * @param array $headers
     * @param array $rows
     * @param string $btnEditRowClass
     * @param string $btnDeleteRowClass
     *
     * @return string
     */
    public function selectResults(array $headers, array $rows, string $btnEditRowClass, string $btnDeleteRowClass): string
    {
        $this->htmlBuilder
            ->table(true, 'bordered')
                ->thead()
                    ->tr();
        foreach ($headers as $header) {
            $this->htmlBuilder
                        ->th($header['key'] ?? '')
                        ->end();
        }
        $this->htmlBuilder
                    ->end()
                ->end()
                ->tbody();
        $rowId = 0;
        foreach($rows as $row) {
            $this->htmlBuilder
                    ->tr()
                        ->th()
                            ->buttonGroup(false)
                                ->button(AbstractBuilder::BTN_PRIMARY + AbstractBuilder::BTN_SMALL, $btnEditRowClass)
                                    ->setDataRowId($rowId)->addIcon('edit')
                                ->end()
                                ->button(AbstractBuilder::BTN_DANGER + AbstractBuilder::BTN_SMALL, $btnDeleteRowClass)
                                    ->setDataRowId($rowId)->addIcon('remove')
                                ->end()
                            ->end()
                        ->end();
            foreach ($row['cols'] as $col) {
                $this->htmlBuilder
                        ->td($col['value'])
                        ->end();
            }
            $this->htmlBuilder
                    ->end();
            $rowId++;
        }
        $this->htmlBuilder
                ->end()
            ->end();
        return $this->htmlBuilder->build();
    }
}
