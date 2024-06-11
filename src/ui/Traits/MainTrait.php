<?php

namespace Lagdo\DbAdmin\Ui\Traits;

use Lagdo\UiBuilder\AbstractBuilder;

trait MainTrait
{
    /**
     * @param array $mainActions
     *
     * @return string
     */
    public function mainActions(array $actions): string
    {
        $backActions = [];
        $mainActions = [];
        foreach($actions as $id => $title)
        {
            if(strpos($id, 'back') !== false || strpos($id, 'cancel') !== false)
            {
                $backActions[$id] = $title;
            }
            else
            {
                $mainActions[$id] = $title;
            }
        }
        $this->htmlBuilder->clear()
            ->buttonGroup(false, ['class' => 'adminer-main-action-group']);
        foreach($mainActions as $id => $title)
        {
            $this->htmlBuilder
                ->button(AbstractBuilder::BTN_OUTLINE)->setId("adminer-main-action-$id")->addText($title)
                ->end();
        }
        $this->htmlBuilder
            ->end()
            ->buttonGroup(false, ['class' => 'adminer-main-action-group', 'style' => 'float:right']);
        foreach($backActions as $id => $title)
        {
            $this->htmlBuilder
                ->button(AbstractBuilder::BTN_SECONDARY)->setId("adminer-main-action-$id")->addText($title)
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
        $last = count($breadcrumbs) - 1;
        $curr = 0;
        foreach($breadcrumbs as $breadcrumb)
        {
            $this->htmlBuilder
                ->breadcrumbItem($curr === $last)->addText($breadcrumb)
                ->end();
            $curr++;
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
                    ->tabNav();
        $active = true;
        foreach($tabs as $id => $tab)
        {
            $this->htmlBuilder
                        ->tabNavItem("tab-content-$id", $active, $tab)
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
