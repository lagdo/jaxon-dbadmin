<?php

namespace Lagdo\DbAdmin\App\Ui\Traits;

use Lagdo\UiBuilder\BuilderInterface;
use Lagdo\UiBuilder\Jaxon\Builder;

use function count;
use function Jaxon\jq;

trait MainTrait
{
    /**
     * @param array $breadcrumbs
     *
     * @return string
     */
    public function breadcrumbs(array $breadcrumbs): string
    {
        $htmlBuilder = Builder::new();
        $htmlBuilder
            ->breadcrumb();
        $last = count($breadcrumbs) - 1;
        $curr = 0;
        foreach($breadcrumbs as $breadcrumb)
        {
            $htmlBuilder
                ->breadcrumbItem($curr === $last)->addText($breadcrumb)
                ->end();
            $curr++;
        }
        $htmlBuilder
            ->end();
        return $htmlBuilder->build();
    }

    /**
     * @param array $actions
     *
     * @return string
     */
    public function pageActions(array $actions): string
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

        $htmlBuilder = Builder::new();
        $htmlBuilder
            ->buttonGroup(false, ['class' => 'adminer-main-action-group']);
        foreach($mainActions as $id => $title)
        {
            $htmlBuilder
                ->button()->btnOutline()->btnPrimary()->setId("adminer-main-action-$id")->addText($title)
                ->end();
        }
        $htmlBuilder
            ->end()
            ->buttonGroup(false, ['class' => 'adminer-main-action-group', 'style' => 'float:right']);
        foreach($backActions as $id => $title)
        {
            $htmlBuilder
                ->button()->btnSecondary()->setId("adminer-main-action-$id")->addText($title)
                ->end();
        }
        $htmlBuilder
            ->end();
        return $htmlBuilder->build();
    }

    /**
     * @param array $tabs
     *
     * @return string
     */
    public function mainDbTable(array $tabs): string
    {
        $htmlBuilder = Builder::new();
        $htmlBuilder
            ->row()
                ->col(12)
                    ->tabNav();
        $active = true;
        foreach($tabs as $id => $tab)
        {
            $htmlBuilder
                        ->tabNavItem("tab-content-$id", $active, $tab)
                        ->end();
            $active = false;
        }
        $htmlBuilder
                    ->end()
                    ->tabContent();
        $active = true;
        foreach($tabs as $id => $tab)
        {
            $htmlBuilder
                        ->tabContentItem("tab-content-$id", $active)
                        ->end();
            $active = false;
        }
        $htmlBuilder
                    ->end()
                ->end()
            ->end();
        return $htmlBuilder->build();
    }

    /**
     * @param BuilderInterface $htmlBuilder
     * @param mixed $content
     *
     * @return void
     */
    private function showTableCell(BuilderInterface $htmlBuilder, $content): void
    {
        if(!is_array($content))
        {
            $htmlBuilder->addHtml($content);
            return;
        }

        if(isset($content['props']))
        {
            $htmlBuilder->setAttributes($content['props']);
        }
        if(!isset($content['handler']))
        {
            $htmlBuilder->addHtml($content['label']);
            return;
        }

        $htmlBuilder
            ->a()
                ->setAttributes(['href' => 'javascript:void(0)'])
                ->jxnClick($content['handler'])
                ->addText($content['label'])
            ->end();
    }

    /**
     * @param BuilderInterface $htmlBuilder
     * @param array $content
     * @param string $counterId
     *
     * @return void
     */
    private function makeTable(BuilderInterface $htmlBuilder, array $content, string $counterId): void
    {
        $headers = $content['headers'] ?? [];
        $details = $content['details'] ?? [];

        $htmlBuilder
            ->table(true, 'bordered');
        if(count($headers) > 0)
        {
            $htmlBuilder
                ->thead()
                    ->tr();
            if($counterId !== '')
            {
                $htmlBuilder
                        ->th()
                            ->input([
                                'type' => 'checkbox',
                                'class' => 'adminer-table-checkbox',
                                'id' => "adminer-table-$counterId-all",
                            ])
                            ->end()
                        ->end();
            }
            foreach($headers as $header)
            {
                $htmlBuilder
                        ->th()
                            ->addHtml($header)
                        ->end();
            }
            $htmlBuilder
                    ->end()
                ->end();
        }

        $htmlBuilder
                ->tbody();
        foreach($details as $_details)
        {
            $htmlBuilder
                    ->tr();
            if($counterId !== '')
            {
                $htmlBuilder
                        ->td()
                            ->input([
                                'type' => 'checkbox',
                                'class' => "adminer-table-$counterId",
                                'name' => "{$counterId}[]",
                            ])
                            ->end()
                        ->end();
            }
            foreach($_details as $detail)
            {
                $htmlBuilder
                        ->td();
                $this->showTableCell($htmlBuilder, $detail ?? '');
                $htmlBuilder
                        ->end();
            }
            $htmlBuilder
                    ->end();
        }
        $htmlBuilder
                ->end()
            ->end();
    }

    /**
     * @param array $pageContent
     * @param string $counterId
     *
     * @return string
     */
    public function mainContent(array $pageContent, string $counterId = ''): string
    {
        $htmlBuilder = Builder::new();
        $this->makeTable($htmlBuilder, $pageContent, $counterId);

        if ($counterId !== '') {
            $htmlBuilder
                ->panel()
                    ->panelBody()
                        ->addHtml('Selected (<span id="adminer-table-' . $counterId . '-count">0</span>)')
                    ->end()
                ->end();
        }
        return $htmlBuilder->build();
    }
}
