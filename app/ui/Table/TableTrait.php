<?php

namespace Lagdo\DbAdmin\Ui\Table;

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
            $this->ui->row(
                $this->ui->col(
                    $this->ui->tabNav(
                        $this->ui->each($tabs, fn($tab, $id) =>
                            $this->ui->tabNavItem($this->ui->text($tab))
                                ->target("tab-content-$id")
                                ->active($firstTabId === $id)
                        )
                    ),
                    $this->ui->tabContent(
                        $this->ui->each($tabs, fn($_, $id) =>
                            $this->ui->tabContentItem()
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
