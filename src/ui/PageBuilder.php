<?php

namespace Lagdo\DbAdmin\Ui;

use Lagdo\DbAdmin\App\Ajax\Admin;
use Lagdo\DbAdmin\App\Ajax\Menu\Db;
use Lagdo\DbAdmin\App\Ajax\Menu\DbActions;
use Lagdo\DbAdmin\App\Ajax\Menu\DbList;
use Lagdo\DbAdmin\App\Ajax\Menu\SchemaList;
use Lagdo\DbAdmin\App\Ajax\Menu\Server;
use Lagdo\DbAdmin\App\Ajax\Menu\ServerActions;
use Lagdo\DbAdmin\App\Ajax\Page\PageActions;
use Lagdo\DbAdmin\App\Ajax\Page\Breadcrumbs;
use Lagdo\DbAdmin\App\Ajax\Page\Content;
use Lagdo\UiBuilder\Jaxon\Builder;

use function count;
use function Jaxon\pm;
use function Jaxon\rq;

class PageBuilder
{
    /**
     * @param array $servers
     * @param string $default
     *
     * @return string
     */
    public function home(array $servers, string $default): string
    {
        $htmlBuilder = Builder::new();
        $htmlBuilder
            ->row()->setId('jaxon-dbadmin')
                ->col(3)
                    ->row()
                        ->col(12)
                            ->inputGroup()
                                ->formSelect()
                                    ->setId('dbadmin-database-server-select');
        foreach($servers as $serverId => $server)
        {
            $htmlBuilder
                                    ->option($serverId == $default, $server['name'])
                                        ->setValue($serverId)
                                    ->end();
        }
        $onClick = rq(Admin::class)->server(pm()->select('dbadmin-database-server-select'));
        $htmlBuilder
                                ->end()
                                ->button()->btnPrimary()
                                    ->setClass('btn-select')
                                    ->addText('Show')
                                    ->jxnClick($onClick)
                                ->end()
                            ->end()
                        ->end()
                        ->col(12)
                            ->jxnShow(rq(ServerActions::class))
                        ->end()
                        ->col(12)
                            ->jxnShow(rq(DbList::class))
                        ->end()
                        ->col(12)
                            ->jxnShow(rq(SchemaList::class))
                        ->end()
                        ->col(12)
                            ->jxnShow(rq(DbActions::class))
                        ->end()
                        ->col(12)
                            ->jxnShow(rq(Db::class))
                        ->end()
                    ->end()
                ->end()
                ->col(9)
                    ->row()
                        ->jxnShow(rq(Server::class))
                    ->end()
                    ->row()
                        ->col(12)
                            ->span()
                                ->jxnShow(rq(Breadcrumbs::class))
                            ->end()
                            ->span()
                                ->jxnShow(rq(PageActions::class))
                            ->end()
                        ->end()
                    ->end()
                    ->row()
                        ->col(12)
                            ->jxnShow(rq(Content::class))
                        ->end()
                    ->end()
                ->end()
            ->end();
        return $htmlBuilder->build();
    }

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
                ->breadcrumbItem($curr++ === $last)
                    ->addText($breadcrumb)
                ->end();
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
        $htmlBuilder = Builder::new();
        $htmlBuilder
            ->buttonGroup(false, ['class' => 'adminer-main-action-group']);
        foreach($actions as $action)
        {
            $isSecondary = $action[2] ?? false;
            $htmlBuilder
                ->button()->btnSecondary();
            if($isSecondary)
            {
                $htmlBuilder
                    ->btnSecondary();
            }
            else
            {
                $htmlBuilder
                    ->btnDanger();
            }
            $htmlBuilder
                    ->addText($action['title'])
                    ->jxnClick($action['handler'])
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
     * @param string $content
     * @param string $counterId
     *
     * @return string
     */
    public function mainContent(string $content, string $counterId = ''): string
    {
        $htmlBuilder = Builder::new();
        $htmlBuilder
            ->table(true, 'bordered')->addHtml($content)
            ->end();
        if (($counterId)) {
            $htmlBuilder
                ->panel()
                    ->panelBody()->addHtml('Selected (<span id="adminer-table-' . $counterId . '-count">0</span>)')
                    ->end()
                ->end();
        }
        return $htmlBuilder->build();
    }
}
