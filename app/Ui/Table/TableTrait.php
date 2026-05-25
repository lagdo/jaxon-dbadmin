<?php

namespace Lagdo\DbAdmin\App\Ui\Table;

use Lagdo\DbAdmin\App\Ui\Tab\Tab;
use Lagdo\UiBuilder\BuilderInterface;

use function array_key_first;

trait TableTrait
{
    /**
     * @var BuilderInterface
     */
    protected BuilderInterface $ui;

    /**
     * @return Tab
     */
    abstract protected function tab(): Tab;

    /**
     * @param array $tabs
     *
     * @return string
     */
    public function mainDbTable(array $tabs): string
    {
        $tabContentId = $this->tab()->app()->id('dbadmin-db-table');
        $firstTabId = array_key_first($tabs);

        return $this->ui->build(
            $this->ui->tabs(
                $this->ui->tabNav(
                    $this->ui->each($tabs, fn($tab, $id) =>
                        $this->ui->tabNavItem($this->ui->text($tab))
                            ->target($this->tab()->app()->id("tab-content-$id"))
                            ->active($firstTabId === $id)
                    )
                )->setStyle('margin-bottom: 5px;'),
                $this->ui->tabContent(
                    $this->ui->each($tabs, fn($_, $id) =>
                        $this->ui->tabContentItem()
                            ->setId($this->tab()->app()->id("tab-content-$id"))
                            ->active($firstTabId === $id)
                    )
                )->setId($tabContentId)
            )->content($tabContentId)
        );
    }
}
