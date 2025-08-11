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
        $html = $this->builder();
        return $html->build(
            $html->row(
                $html->col(
                    $html->tabNav(
                        $html->each($tabs, fn($tab, $id) =>
                            $html->tabNavItem($html->text($tab))
                                ->target("tab-content-$id")
                                ->active($firstTabId === $id)
                        )
                    ),
                    $html->tabContent(
                        $html->each($tabs, fn($_, $id) =>
                            $html->tabContentItem()
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
