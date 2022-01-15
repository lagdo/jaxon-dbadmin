<?php

namespace Lagdo\DbAdmin\Ui\Traits;

trait MainTrait
{
    /**
     * @param array $mainActions
     *
     * @return string
     */
    public function mainActions(array $mainActions): string
    {
        $this->htmlBuilder->clear()
            ->buttonGroup(false, 'adminer-main-action-group');
        foreach($mainActions as $id => $title)
        {
            $this->htmlBuilder
                ->button($title, 'default', '', false)->setId("adminer-main-action-$id")
                ->end();
        }
        $this->htmlBuilder
            ->end();
        return $this->htmlBuilder->build();
    }

    /**
     * @param array $breadcrumbs
     *
     * @return string
     */
    public function breadcrumbs(array $breadcrumbs): string
    {
        $this->htmlBuilder->clear()
            ->breadcrumb();
        foreach($breadcrumbs as $breadcrumb)
        {
            $this->htmlBuilder
                ->breadcrumbItem($breadcrumb)
                ->end();
        }
        $this->htmlBuilder
            ->end();
        return $this->htmlBuilder->build();
    }

    /**
     * @param array $tabs
     *
     * @return string
     */
    public function mainDbTable(array $tabs): string
    {
        $this->htmlBuilder->clear()
            ->row()
                ->col(12)
                    ->tabHeader();
        $active = true;
        foreach($tabs as $id => $tab)
        {
            $this->htmlBuilder
                        ->tabHeaderItem("tab-content-$id", $active, $tab)
                        ->end();
            $active = false;
        }
        $this->htmlBuilder
                    ->end()
                    ->tabContent();
        $active = true;
        foreach($tabs as $id => $tab)
        {
            $this->htmlBuilder
                        ->tabContentItem("tab-content-$id", $active)
                        ->end();
            $active = false;
        }
        $this->htmlBuilder
                    ->end()
                ->end()
            ->end();
        return $this->htmlBuilder->build();
    }

    /**
     * @param string $content
     * @param string $counterId
     *
     * @return string
     */
    public function mainContent(string $content, string $counterId = ''): string
    {
        $this->htmlBuilder->clear()
            ->table(true, 'bordered')->addHtml($content)
            ->end();
        if (($counterId)) {
            $this->htmlBuilder
                ->panel()
                    ->panelBody()->addHtml('Selected (<span id="adminer-table-' . $counterId . '-count">0</span>)')
                    ->end()
                ->end();
        }
        return $this->htmlBuilder->build();
    }
}
