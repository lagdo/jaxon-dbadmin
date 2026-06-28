<?php

namespace Lagdo\DbAdmin\App\Ui;

use Lagdo\DbAdmin\App\Ajax\Admin\DbFunc;
use Lagdo\DbAdmin\App\Ajax\Admin\Menu\Database\Command as DatabaseCommand;
use Lagdo\DbAdmin\App\Ajax\Admin\Menu\Database\Schemas as MenuSchemas;
use Lagdo\DbAdmin\App\Ajax\Admin\Menu\Sections as MenuSections;
use Lagdo\DbAdmin\App\Ajax\Admin\Menu\Server\Command as ServerCommand;
use Lagdo\DbAdmin\App\Ajax\Admin\Menu\Server\Databases as MenuDatabases;
use Lagdo\DbAdmin\App\Ajax\Admin\Page\AppTabMenu;
use Lagdo\DbAdmin\App\Ajax\Admin\Page\Breadcrumbs;
use Lagdo\DbAdmin\App\Ajax\Admin\Page\Content;
use Lagdo\DbAdmin\App\Ajax\Admin\Page\PageActions;
use Lagdo\DbAdmin\App\Ajax\Audit\Content as AuditContent;
use Lagdo\DbAdmin\App\Ajax\Audit\Sidebar as AuditSidebar;
use Lagdo\DbAdmin\App\Ui\Tab\Tab;
use Lagdo\DbAdmin\Support\Translator;
use Lagdo\UiBuilder\BuilderInterface;

use function count;
use function Jaxon\cl;
use function Jaxon\rq;
use function Jaxon\select;

class UiBuilder
{
    use PageTrait;
    use UiTabTrait;

    /**
     * @param Translator $trans
     * @param BuilderInterface $ui
     * @param Tab $tab
     */
    public function __construct(protected Translator $trans,
        protected BuilderInterface $ui, protected Tab $tab)
    {}

    /**
     * @return Tab
     */
    protected function tab(): Tab
    {
        return $this->tab;
    }

    /**
     * @return string
     */
    public function hostSelectId(): string
    {
        return $this->tab()->app()->id('jaxon-dbadmin-dbhost-select');
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
            $this->tab()->app()->id("dbadmin-table-$contentType"),
            $this->tab()->app()->wrapperId(),
        ];
    }

    /**
     * @param array<string> $servers
     * @param bool $serverAccess
     * @param string $default
     *
     * @return string
     */
    public function sidebar(array $servers, bool $serverAccess, string $default): string
    {
        return $this->ui->build(
            $this->ui->div(
                $this->ui->form(
                    $this->ui->inputGroup(
                        $this->ui->select(
                            $this->ui->each($servers, fn($serverName, $serverId) =>
                                $this->ui->option($serverName)
                                    ->selected($serverId === $default)
                                    ->setValue($serverId)
                            )
                        )->setId($this->hostSelectId()),
                        $this->ui->button($this->ui->text('Show'))
                            ->primary()
                            ->setClass('btn-select')
                            ->jxnClick(rq(DbFunc::class)
                                ->server(select($this->hostSelectId())))
                    )
                )
            )->setStyle('margin-bottom: 10px;'),
            $this->ui->when($serverAccess, fn() =>
                $this->ui->div()
                    ->setStyle('margin-bottom: 10px;')
                    ->tbnBindApp(rq(ServerCommand::class))
            ),
            $this->ui->div()
                ->setStyle('margin-bottom: 10px;')
                ->tbnBindApp(rq(MenuDatabases::class)),
            $this->ui->div()
                ->setStyle('margin-bottom: 10px;')
                ->tbnBindApp(rq(MenuSchemas::class)),
            $this->ui->div()
                ->setStyle('margin-bottom: 10px;')
                ->tbnBindApp(rq(DatabaseCommand::class)),
            $this->ui->div()
                ->setStyle('margin-bottom: 10px;')
                ->tbnBindApp(rq(MenuSections::class))
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
            )->setStyle('margin: 2px 0;')
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
    public function content(): string
    {
        return $this->ui->build(
            $this->ui->div(
                $this->ui->div(
                    $this->ui->div(
                        $this->ui->div()->tbnBindApp(rq(Breadcrumbs::class))
                    )->setClass('jaxon-dbadmin-server-header-breadcrumbs'),
                    $this->ui->div(
                        $this->ui->div()->tbnBindApp(rq(PageActions::class))
                    )->setClass('jaxon-dbadmin-server-header-actions')
                )->setClass('jaxon-dbadmin-server-header'),
                $this->ui->div()
                    ->setClass('jaxon-dbadmin-server-wrapper')
                    ->tbnBindApp(rq(Content::class))
            )->setClass('jaxon-dbadmin-server-wrapper')
        );
    }

    /**
     * The DbAdmin layout
     *
     * @return string
     */
    public function admin(): string
    {
        $contentId = 'dbadmin-server-tab-content';

        return $this->ui->build(
            $this->ui->div(
                $this->ui->div(
                    $this->ui->div(
                        $this->ui->text('Jaxon DbAdmin')
                    )->setClass('jaxon-dbadmin-page-header_title'),
                    $this->ui->tabs(
                        $this->ui->tabNav(
                            $this->tabNavItem('&nbsp;', true)
                        )->setId('dbadmin-server-tab-nav')
                    )->content($contentId)
                        ->setClass('jaxon-dbadmin-page-header_items'),
                    $this->ui->div()
                        ->jxnBind(rq(AppTabMenu::class))
                        ->setClass('jaxon-dbadmin-page-header_menus')
                )->setClass('jaxon-dbadmin-page-header'),
                $this->ui->tabContent(
                    $this->tabContentItem(true)
                )->setId($contentId)
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
                        $this->ui->text('Jaxon DbAdmin Audit Logs')
                    )->setClass('jaxon-dbadmin-page-header_title'),
                    $this->ui->div('&nbsp;')
                        ->setClass('jaxon-dbadmin-page-header_spacer'),
                )->setClass('jaxon-dbadmin-page-header'),
                $this->ui->div(
                    $this->ui->div(
                        $this->ui->div(
                            cl(AuditSidebar::class)->html()
                        )->setClass('jaxon-dbadmin-page-sidebar_block')
                            ->jxnBind(rq(AuditSidebar::class))
                    )->setClass('jaxon-dbadmin-page-sidebar'),
                    $this->ui->div(
                        $this->ui->div(
                            cl(AuditContent::class)->html()
                        )->jxnBind(rq(AuditContent::class))
                    )->setClass('jaxon-dbadmin-page-content')
                )->setClass('jaxon-dbadmin-page-wrapper')
            )->setId('jaxon-dbadmin')
        );
    }
}
