<?php

namespace Lagdo\DbAdmin\Ui;

use Jaxon\JsCall\AttrFormatter;
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
use Lagdo\UiBuilder\AbstractBuilder;
use Lagdo\UiBuilder\BuilderInterface;

use function count;
use function Jaxon\pm;
use function Jaxon\rq;

class PageBuilder
{
    /**
     * @param BuilderInterface $htmlBuilder
     * @param AttrFormatter $attr
     */
    public function __construct(private BuilderInterface $htmlBuilder, private AttrFormatter $attr)
    {}

    /**
     * @param array $servers
     * @param string $default
     *
     * @return string
     */
    public function home(array $servers, string $default): string
    {
        $this->htmlBuilder->clear()
            ->row()->setId('jaxon-dbadmin')
                ->col(3)
                    ->row()
                        ->col(12)
                            ->inputGroup()
                                ->formSelect()
                                    ->setId('dbadmin-database-server-select');
        foreach($servers as $serverId => $server)
        {
            $this->htmlBuilder
                                    ->option($serverId == $default, $server['name'])
                                        ->setValue($serverId)
                                    ->end();
        }
        $onClick = rq(Admin::class)->server(pm()->select('dbadmin-database-server-select'));
        $this->htmlBuilder
                                ->end()
                                ->button(AbstractBuilder::BTN_PRIMARY)
                                    ->setClass('btn-select')
                                    ->addText('Show')
                                    ->setJxnClick($this->attr->func($onClick))
                                ->end()
                            ->end()
                        ->end()
                        ->col(12)
                            ->setJxnShow($this->attr->show(rq(ServerActions::class)))
                        ->end()
                        ->col(12)
                            ->setJxnShow($this->attr->show(rq(DbList::class)))
                        ->end()
                        ->col(12)
                            ->setJxnShow($this->attr->show(rq(SchemaList::class)))
                        ->end()
                        ->col(12)
                            ->setJxnShow($this->attr->show(rq(DbActions::class)))
                        ->end()
                        ->col(12)
                            ->setJxnShow($this->attr->show(rq(Db::class)))
                        ->end()
                    ->end()
                ->end()
                ->col(9)
                    ->row()
                        ->setJxnShow($this->attr->show(rq(Server::class)))
                    ->end()
                    ->row()
                        ->col(12)
                            ->span()
                                ->setJxnShow($this->attr->show(rq(Breadcrumbs::class)))
                            ->end()
                            ->span()
                                ->setJxnShow($this->attr->show(rq(PageActions::class)))
                            ->end()
                        ->end()
                    ->end()
                    ->row()
                        ->col(12)
                            ->setJxnShow($this->attr->show(rq(Content::class)))
                        ->end()
                    ->end()
                ->end()
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
                ->breadcrumbItem($curr++ === $last)
                    ->addText($breadcrumb)
                ->end();
        }
        $this->htmlBuilder
            ->end();
        return $this->htmlBuilder->build();
    }

    /**
     * @param array $actions
     *
     * @return string
     */
    public function pageActions(array $actions): string
    {
        $this->htmlBuilder->clear()
            ->buttonGroup(false, ['class' => 'adminer-main-action-group']);
        foreach($actions as $action)
        {
            $isSecondary = $action[2] ?? false;
            $btnType = $isSecondary ? AbstractBuilder::BTN_SECONDARY : AbstractBuilder::BTN_OUTLINE;
            $this->htmlBuilder
                ->button($btnType)
                    ->addText($action[0])
                    ->setJxnClick($action[1])
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
