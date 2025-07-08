<?php

namespace Lagdo\DbAdmin\Ui;

use Lagdo\DbAdmin\Ajax\App\Admin;
use Lagdo\DbAdmin\Ajax\App\Menu\Sections as MenuSections;
use Lagdo\DbAdmin\Ajax\App\Menu\Database\Command as DatabaseCommand;
use Lagdo\DbAdmin\Ajax\App\Menu\Database\Schemas as MenuSchemas;
use Lagdo\DbAdmin\Ajax\App\Menu\Server\Command as ServerCommand;
use Lagdo\DbAdmin\Ajax\App\Menu\Server\Databases as MenuDatabases;
use Lagdo\DbAdmin\Ajax\App\Page\Breadcrumbs;
use Lagdo\DbAdmin\Ajax\App\Page\Content;
use Lagdo\DbAdmin\Ajax\App\Page\PageActions;
use Lagdo\DbAdmin\Ajax\App\Page\ServerInfo;
use Lagdo\UiBuilder\BuilderInterface;

use function array_key_first;
use function count;
use function Jaxon\pm;
use function Jaxon\rq;

class PageBuilder
{
    /**
     * @param BuilderInterface $html
     */
    public function __construct(protected BuilderInterface $html)
    {}

    /**
     * @param array $servers
     * @param string $default
     *
     * @return mixed
     */
    private function getServerSelectCol(array $servers, string $default): mixed
    {
        $onClick = rq(Admin::class)
            ->server(pm()->select('dbadmin-database-server-select'));
        return $this->html->col(
            $this->html->inputGroup(
                $this->html->formSelect(
                    $this->html->each($servers, fn($server, $serverId) =>
                        $this->html->option($server['name'])
                            ->selected($serverId == $default)
                            ->setValue($serverId)
                    )
                )
                ->setId('dbadmin-database-server-select'),
                $this->html->button()
                    ->primary()
                    ->setClass('btn-select')
                    ->addText('Show')
                    ->jxnClick($onClick),
            )
        );
    }

    /**
     * @param array $servers
     * @param string $default
     *
     * @return string
     */
    public function home(array $servers, string $default): string
    {
        return $this->html->build(
            $this->html->row(
                $this->html->col(
                    $this->html->row(
                        $this->getServerSelectCol($servers, $default)
                            ->width(12),
                        $this->html->col()
                            ->width(12)
                            ->jxnBind(rq(ServerCommand::class)),
                        $this->html->col()
                            ->width(12)
                            ->jxnBind(rq(MenuDatabases::class)),
                        $this->html->col()
                            ->width(12)
                            ->jxnBind(rq(MenuSchemas::class)),
                        $this->html->col()
                            ->width(12)
                            ->jxnBind(rq(DatabaseCommand::class)),
                        $this->html->col()
                            ->width(12)
                            ->jxnBind(rq(MenuSections::class))
                    )
                )
                ->width(3),
                $this->html->col(
                    $this->html->row()
                        ->jxnBind(rq(ServerInfo::class)),
                    $this->html->row(
                        $this->html->col(
                            $this->html->span()
                                ->jxnBind(rq(Breadcrumbs::class)),
                            $this->html->span()
                                ->jxnBind(rq(PageActions::class))
                        )
                        ->width(12)
                    ),
                    $this->html->row(
                        $this->html->col()
                            ->width(12)
                            ->jxnBind(rq(Content::class))
                    ),
                )
                ->width(9),
            )
            ->setId('jaxon-dbadmin')
        );
    }

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
                $this->html->each($breadcrumbs, fn($breadcrumb) =>
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
        return $this->html->build(
            $this->html->buttonGroup(
                $this->html->each($actions, fn($action, $class) =>
                    $this->html->button(['class' => $class])
                        ->outline()->secondary()
                        ->addText($action['title'])
                        ->jxnClick($action['handler'])
                )
            )
            ->setClass('dbadmin-main-action-group')
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
                                ->setId("tab-content-$id")
                                ->active($firstTabId === $id)->addText($tab)
                        )
                    ),
                    $this->html->tabContent(
                        $this->html->each($tabs, fn($tab, $id) =>
                            $this->html->tabContentItem()
                                ->setId("tab-content-$id")
                                ->active($firstTabId === $id)
                        )
                    )
                )
                ->width(12)
            )
        );
    }

    /**
     * @param string $content
     * @param string $counterId
     *
     * @return string
     */
    public function mainContent(string $content, string $counterId = ''): string
    {
        return $this->html->build(
            $this->html->table(
                $this->html->when($counterId !== '', fn() =>
                    $this->html->panel(
                        $this->html->panelBody()
                            ->addHtml('Selected (<span id="dbadmin-table-' .
                                $counterId . '-count">0</span>)')
                    )
                )
            )
            ->responsive(true)->style('bordered')
            ->addHtml($content)
        );
    }
}
