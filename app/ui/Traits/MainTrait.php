<?php

namespace Lagdo\DbAdmin\Ui\Traits;

use Lagdo\UiBuilder\BuilderInterface;

use function array_key_first;
use function count;
use function strpos;

trait MainTrait
{
    /**
     * @return BuilderInterface
     */
    abstract protected function builder(): BuilderInterface;

    /**
     * @param array $breadcrumbs
     *
     * @return string
     */
    public function breadcrumbs(array $breadcrumbs): string
    {
        $last = count($breadcrumbs) - 1;
        $curr = 0;
        $html = $this->builder();
        return $html->build(
            $html->breadcrumb(
                $html->each($breadcrumbs, fn($breadcrumb)  =>
                    $html->breadcrumbItem()
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
        $html = $this->builder();
        return $html->build(
            $html->buttonGroup(
                $html->each($mainActions, fn($title, $id) =>
                    $html->button()->outline()->primary()
                        ->setId("dbadmin-main-action-$id")->addText($title)
                )
            )
            ->setClass('dbadmin-main-action-group'),
            $html->buttonGroup(
                $html->each($backActions, fn($title, $id) =>
                    $html->button()->secondary()
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
        $html = $this->builder();
        return $html->build(
            $html->row(
                $html->col(
                    $html->tabNav(
                        $html->each($tabs, fn($tab, $id) =>
                            $html->tabNavItem()
                                ->target("tab-content-$id")
                                ->active($firstTabId === $id)->addText($tab)
                        )
                    ),
                    $html->tabContent(
                        $html->each($tabs, fn($_, $id) =>
                            $html->tabContentItem()
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
        $html = $this->builder();
        $element = $html->td();
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
            $html->a()
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
        $html = $this->builder();
        return $html->table(
            $html->thead(
                $html->when($counterId !== '', fn() =>
                    $html->th(
                        $html->checkbox()
                            ->addClass('dbadmin-table-checkbox')
                            ->setId("dbadmin-table-$counterId-all")
                    )
                ),
                $html->each($headers, fn($header) =>
                    $html->th()->addHtml($header)
                ),
            ),
            $html->body(
                $html->each($details, fn($detailGroup) =>
                    $html->tr(
                        $html->when($counterId !== '', fn() =>
                            $html->td(
                                $html->checkbox()
                                    ->addClass("dbadmin-table-$counterId")
                                    ->setName("{$counterId}[]")
                            )
                        ),
                        $html->each($detailGroup, fn($detail) =>
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
        $html = $this->builder();
        return $html->build(
            $this->makeTable($pageContent, $counterId),
            $html->when($counterId !== '', fn() =>
                 $html->panel(
                    $html->panelBody()
                        ->addHtml('Selected (<span id="dbadmin-table-' . $counterId . '-count">0</span>)')
                )
            )
        );
    }
}
