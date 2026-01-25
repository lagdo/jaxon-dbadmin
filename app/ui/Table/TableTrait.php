<?php

namespace Lagdo\DbAdmin\Ui\Table;

use Lagdo\DbAdmin\Ui\Tab;
use Lagdo\UiBuilder\BuilderInterface;

use function array_key_first;

trait TableTrait
{
    /**
     * @var BuilderInterface
     */
    protected BuilderInterface $ui;

    /**
     * @param array $tabs
     *
     * @return string
     */
    public function mainDbTable(array $tabs): string
    {
        $firstTabId = array_key_first($tabs);
        return $this->ui->build(
            $this->ui->tabNav(
                $this->ui->each($tabs, fn($tab, $id) =>
                    $this->ui->tabNavItem($this->ui->text($tab))
                        ->target(Tab::id("tab-content-$id"))
                        ->active($firstTabId === $id)
                )
            )->setStyle('margin-bottom: 5px;'),
            $this->ui->tabContent(
                $this->ui->each($tabs, fn($_, $id) =>
                    $this->ui->tabContentItem()
                        ->setId(Tab::id("tab-content-$id"))
                        ->active($firstTabId === $id)
                )
            )
        );
    }
}
