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
                    $html->breadcrumbItem($this->html->text($breadcrumb))
                        ->active($curr++ === $last)
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
                    $html->button($this->html->text($title))->outline()->primary()
                        ->setId("dbadmin-main-action-$id")
                )
            )
            ->setClass('dbadmin-main-action-group'),
            $html->buttonGroup(
                $html->each($backActions, fn($title, $id) =>
                    $html->button($this->html->text($title))->secondary()
                        ->setId("dbadmin-main-action-$id")
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
                            $html->tabNavItem($this->html->text($tab))
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
        if (!is_array($content)) {
            return $html->td($html->html($content));
        }

        if(!isset($content['handler']))
        {
            return $html->td($html->text($content['label']));
        }

        $element = $html->td();
        if(isset($content['props']))
        {
            $element->setAttributes($content['props']);
        }

        $element->children(
            $html->a($this->html->text($content['label']))
                ->setAttributes(['href' => 'javascript:void(0)'])
                ->jxnClick($content['handler'])
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
                    $html->th($html->html($header))
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
                    $html->panelBody($html->html('Selected (<span id="dbadmin-table-' .
                        $counterId . '-count">0</span>)'))
                )
            )
        );
    }
}
