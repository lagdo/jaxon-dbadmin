<?php

namespace Lagdo\DbAdmin\Ui\Table;

use Lagdo\UiBuilder\BuilderInterface;

use function array_key_first;

trait TableTrait
{
    /**
     * @return BuilderInterface
     */
    abstract protected function builder(): BuilderInterface;

    /**
     * @param array $tabs
     *
     * @return string
     */
    public function mainDbTable(array $tabs): string
    {
        $firstTabId = array_key_first($tabs);
        $ui = $this->builder();
        return $ui->build(
            $ui->row(
                $ui->col(
                    $ui->tabNav(
                        $ui->each($tabs, fn($tab, $id) =>
                            $ui->tabNavItem($ui->text($tab))
                                ->target("tab-content-$id")
                                ->active($firstTabId === $id)
                        )
                    ),
                    $ui->tabContent(
                        $ui->each($tabs, fn($_, $id) =>
                            $ui->tabContentItem()
                                ->setId("tab-content-$id")
                                ->active($firstTabId === $id)
                        )
                    )
                )
                ->width(12)
            )
        );
    }
}
