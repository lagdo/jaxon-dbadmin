<?php

namespace Lagdo\DbAdmin\Ui;

use Lagdo\DbAdmin\Ajax\Admin\Admin;
use Lagdo\DbAdmin\Ajax\Admin\Sidebar as AdminSidebar;
use Lagdo\DbAdmin\Ajax\Admin\Wrapper as AdminWrapper;
use Lagdo\DbAdmin\Ajax\Admin\Menu\Sections as MenuSections;
use Lagdo\DbAdmin\Ajax\Admin\Menu\Database\Command as DatabaseCommand;
use Lagdo\DbAdmin\Ajax\Admin\Menu\Database\Schemas as MenuSchemas;
use Lagdo\DbAdmin\Ajax\Admin\Menu\Server\Command as ServerCommand;
use Lagdo\DbAdmin\Ajax\Admin\Menu\Server\Databases as MenuDatabases;
use Lagdo\DbAdmin\Ajax\Admin\Page\Breadcrumbs;
use Lagdo\DbAdmin\Ajax\Admin\Page\Content;
use Lagdo\DbAdmin\Ajax\Admin\Page\PageActions;
use Lagdo\DbAdmin\Ajax\Admin\Page\DbConnection;
use Lagdo\DbAdmin\Ajax\Audit\Sidebar as AuditSidebar;
use Lagdo\DbAdmin\Ajax\Audit\Wrapper as AuditWrapper;
use Lagdo\DbAdmin\Db\Translator;
use Lagdo\DbAdmin\Ui\Tab;
use Lagdo\UiBuilder\BuilderInterface;
use Lagdo\UiBuilder\Component\HtmlComponent;

use function count;
use function array_shift;
use function Jaxon\cl;
use function Jaxon\jo;
use function Jaxon\rq;
use function Jaxon\select;

class UiBuilder
{
    use PageTrait;

    /**
     * @param Translator $trans
     * @param BuilderInterface $ui
     */
    public function __construct(protected Translator $trans, protected BuilderInterface $ui)
    {}

    /**
     * @return string
     */
    public static function hostSelectId(): string
    {
        return Tab::id('jaxon-dbadmin-dbhost-select');
    }

    /**
     * @param string $contentType
     *
     * @return array<string>
     */
    public function contentIds(string $contentType): array
    {
        return [
            "dbadmin-table-$contentType",
            Tab::id("dbadmin-table-$contentType"),
            Tab::wrapperId(),
        ];
    }

    /**
     * @param array $servers
     * @param string $default
     *
     * @return mixed
     */
    private function getHostSelectCol(array $servers, string $default): mixed
    {
        return $this->ui->col(
            $this->ui->form(
                $this->ui->inputGroup(
                    $this->ui->select(
                        $this->ui->each($servers, fn($server, $serverId) =>
                            $this->ui->option($server['name'])
                                ->selected($serverId === $default)
                                ->setValue($serverId)
                        )
                    )->setId(self::hostSelectId()),
                    $this->ui->button($this->ui->text('Show'))
                        ->primary()
                        ->setClass('btn-select')
                        ->jxnClick(rq(Admin::class)
                            ->server(select(self::hostSelectId())))
                )
            )
        );
    }

    /**
     * @param array $servers
     * @param bool $serverAccess
     * @param string $default
     *
     * @return string
     */
    public function sidebar(array $servers, bool $serverAccess, string $default): string
    {
        return $this->ui->build(
            $this->ui->row(
                $this->getHostSelectCol($servers, $default)
                    ->width(12)
            ),
            $this->ui->when($serverAccess, fn() =>
                $this->ui->row(
                    $this->ui->col()
                        ->width(12)
                        ->tbnBind(rq(ServerCommand::class))
                )
            ),
            $this->ui->row(
                $this->ui->col()
                    ->width(12)
                    ->tbnBind(rq(MenuDatabases::class))
            ),
            $this->ui->row(
                $this->ui->col()
                    ->width(12)
                    ->tbnBind(rq(MenuSchemas::class))
            ),
            $this->ui->row(
                $this->ui->col()
                    ->width(12)
                    ->tbnBind(rq(DatabaseCommand::class))
            ),
            $this->ui->row(
                $this->ui->col()
                    ->width(12)
                    ->tbnBind(rq(MenuSections::class))
            )
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
        return $this->ui->build(
            $this->ui->breadcrumb(
                $this->ui->each($breadcrumbs, fn($breadcrumb) =>
                    $this->ui->breadcrumbItem($this->ui->html($breadcrumb))
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
    public function actions(array $actions): string
    {
        return $this->ui->build(
            $this->ui->buttonGroup(
                $this->ui->each($actions, fn($action, $class) =>
                    $this->ui->button(['class' => $class],
                        $this->ui->text($action['title'])
                    )->outline()
                        ->secondary()
                        ->jxnClick($action['handler'])
                )
            )->setClass('dbadmin-main-action-group')
        );
    }

    /**
     * @return string
     */
    public function wrapper(): string
    {
        return $this->ui->build(
            $this->ui->row()->tbnBind(rq(DbConnection::class)),
            $this->ui->row(
                $this->ui->col(
                    $this->ui->span(['style' => 'float:left'])
                        ->tbnBind(rq(Breadcrumbs::class)),
                    $this->ui->span(['style' => 'float:right'])
                        ->tbnBind(rq(PageActions::class))
                )->width(12)
            ),
            $this->ui->row(
                $this->ui->col()
                    ->width(12)
                    ->tbnBind(rq(Content::class))
            )
        );
    }

    /**
     * @param array $menus
     *
     * @return string
     */
    public function tableMenu(array $menus): string
    {
        $menu = array_shift($menus);
        return $this->ui->build(
            $this->ui->buttonGroup(
                $this->ui->button($menu['label'])
                    ->primary()
                    ->jxnClick($menu['handler']),
                $this->ui->dropdownItem()->look('primary'),
                $this->ui->dropdownMenu(
                    $this->ui->each($menus, fn($menu) =>
                        $this->ui->dropdownMenuItem($menu['label'])
                            ->jxnClick($menu['handler'])
                    )
                )
            )
        );
    }

    /**
     * @param string $title
     * @param bool $active
     *
     * @return HtmlComponent
     */
    private function tabNavItem(string $title, bool $active): HtmlComponent
    {
        return $this->ui->tabNavItem($title)
            ->target(Tab::wrapperId())
            ->active($active)
            ->jxnClick(jo('jaxon.dbadmin')->setCurrentTab(Tab::current()));
    }

    /**
     * @param string $title
     *
     * @return string
     */
    public function tabNavItemHtml(string $title): string
    {
        return $this->ui->build(
            $this->tabNavItem($title, false)
        );
    }

    /**
     * @param bool $active
     *
     * @return HtmlComponent
     */
    private function tabContentItem(bool $active): HtmlComponent
    {
        return $this->ui->tabContentItem(
            $this->ui->div(
                $this->ui->div(
                    $this->ui->div(
                        cl(AdminSidebar::class)->html()
                    )->tbnBind(rq(AdminSidebar::class))
                )->setClass('jaxon-dbadmin-layout_sidebar'),
                $this->ui->div(
                    $this->ui->div(
                        cl(AdminWrapper::class)->html()
                    )->tbnBind(rq(AdminWrapper::class))
                )->setClass('jaxon-dbadmin-layout_wrapper')
            )->setClass('jaxon-dbadmin-layout')
        )->setId(Tab::wrapperId())
            ->active($active);
    }

    /**
     * @return string
     */
    public function tabContentItemHtml(): string
    {
        return $this->ui->build(
            $this->tabContentItem( false)
        );
    }

    /**
     * The DbAdmin layout
     *
     * @return string
     */
    public function admin(): string
    {
        return $this->ui->build(
            $this->ui->div(
                $this->ui->row(
                    $this->ui->col(
                        $this->ui->button($this->ui->html('<i class="fa fa-plus"></i>'))
                            ->primary()
                            ->setStyle('float:right;')
                            ->jxnClick(rq(Admin::class)->addTab()),
                    )->width(1)->setStyle('padding-top:17px;'),
                    $this->ui->col(
                        $this->ui->tabNav(
                            $this->tabNavItem('Database tab zero', true)
                        )->setStyle('margin-top: 15px;margin-bottom: 5px;')
                            ->setId('dbadmin-server-tab-nav')
                    )->width(11),
                ),
                $this->ui->tabContent(
                    $this->tabContentItem(true)
                )->setId('dbadmin-server-tab-content')
            )->setId('jaxon-dbadmin')
        );
    }

    /**
     * The DbAudit layout
     *
     * @return string
     */
    public function audit(): string
    {
        return $this->ui->build(
            $this->ui->div(
                $this->ui->div(
                    $this->ui->div(
                        cl(AuditSidebar::class)->html()
                    )->jxnBind(rq(AuditSidebar::class))
                )->setClass('jaxon-dbadmin-layout_sidebar'),
                $this->ui->div(
                    $this->ui->div(
                        cl(AuditWrapper::class)->html()
                    )->jxnBind(rq(AuditWrapper::class))
                )->setClass('jaxon-dbadmin-layout_wrapper')
            )->setClass('jaxon-dbadmin-layout')
        );
    }
}
