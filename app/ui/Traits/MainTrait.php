<?php

namespace Lagdo\DbAdmin\Ui\Traits;

use function array_key_first;
use function count;
use function strpos;

trait MainTrait
{
    /**
     * @param array $breadcrumbs
     *
     * @return string
     */
    public function breadcrumbs(array $breadcrumbs): string
    {
        $last = count($breadcrumbs) - 1;
        $curr = 0;
        return $this->html->build(
            $this->html->breadcrumb(
                $this->html->each($breadcrumbs, fn($breadcrumb)  =>
                    $this->html->breadcrumbItem()
                        ->active($curr++ === $last)->addText($breadcrumb)
                )
            )
        );
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
            if(strpos($id, 'back') !== false ||
                strpos($id, 'cancel') !== false)
            {
                $backActions[$id] = $title;
            }
            else
            {
                $mainActions[$id] = $title;
            }
        }
        return $this->html->build(
            $this->html->buttonGroup(
                $this->html->each($mainActions, fn($title, $id) =>
                    $this->html->button()->outline()->primary()
                        ->setId("dbadmin-main-action-$id")->addText($title)
                )
            )
            ->setClass('dbadmin-main-action-group'),
            $this->html->buttonGroup(
                $this->html->each($backActions, fn($title, $id) =>
                    $this->html->button()->secondary()
                        ->setId("dbadmin-main-action-$id")->addText($title)
                ),
            )
            ->setClass('dbadmin-main-action-group')
            ->setStyle('float:right')
        );
    }

    /**
     * @param array $tabs
     *
     * @return string
     */
    public function mainDbTable(array $tabs): string
    {
        $firstTabId = array_key_first($tabs);
        return $this->html->build(
            $this->html->row(
                $this->html->col(
                    $this->html->tabNav(
                        $this->html->each($tabs, fn($tab, $id) =>
                            $this->html->tabNavItem()
                                ->target("tab-content-$id")
                                ->active($firstTabId === $id)->addText($tab)
                        )
                    ),
                    $this->html->tabContent(
                        $this->html->each($tabs, fn($_, $id) =>
                            $this->html->tabContentItem()
                                ->setId("tab-content-$id")
                                ->active($firstTabId === $id)
                        )
                    ),
                )
                ->width(12)
            )
        );
    }

    /**
     * @param mixed $content
     *
     * @return mixed
     */
    private function getTableCell($content): mixed
    {
        $element = $this->html->td();
        if (!is_array($content)) {
            $element->addHtml($content);
            return $element;
        }

        if(isset($content['props']))
        {
            $element->setAttributes($content['props']);
        }
        if(!isset($content['handler']))
        {
            $element->addText($content['label']);
            return $element;
        }

        $element->children(
            $this->html->a()
                ->setAttributes(['href' => 'javascript:void(0)'])
                ->jxnClick($content['handler'])
                ->addText($content['label'])
        );
        return $element;
    }

    /**
     * @param array $content
     * @param string $counterId
     *
     * @return mixed
     */
    private function makeTable(array $content, string $counterId): mixed
    {
        $headers = $content['headers'] ?? [];
        $details = $content['details'] ?? [];
        return $this->html->table(
            $this->html->thead(
                $this->html->when($counterId !== '', fn() =>
                    $this->html->th(
                        $this->html->checkbox()
                            ->addClass('dbadmin-table-checkbox')
                            ->setId("dbadmin-table-$counterId-all")
                    )
                ),
                $this->html->each($headers, fn($header) =>
                    $this->html->th()->addHtml($header)
                ),
            ),
            $this->html->body(
                $this->html->each($details, fn($detailGroup) =>
                    $this->html->tr(
                        $this->html->when($counterId !== '', fn() =>
                            $this->html->td(
                                $this->html->checkbox()
                                    ->addClass("dbadmin-table-$counterId")
                                    ->setName("{$counterId}[]")
                            )
                        ),
                        $this->html->each($detailGroup, fn($detail) =>
                            $this->getTableCell($detail ?? '')
                        )
                    )
                ),
            ),
        )
        ->responsive()
        ->style('bordered');
    }

    /**
     * @param array $pageContent
     * @param string $counterId
     *
     * @return string
     */
    public function mainContent(array $pageContent, string $counterId = ''): string
    {
        return $this->html->build(
            $this->makeTable($pageContent, $counterId),
            $this->html->when($counterId !== '', fn() =>
                 $this->html->panel(
                    $this->html->panelBody()
                        ->addHtml('Selected (<span id="dbadmin-table-' . $counterId . '-count">0</span>)')
                )
            )
        );
    }
}
