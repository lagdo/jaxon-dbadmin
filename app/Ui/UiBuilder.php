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
use Lagdo\DbAdmin\App\Ajax\Audit\Sidebar as AuditSidebar;
use Lagdo\DbAdmin\App\Ajax\Audit\Wrapper as AuditWrapper;
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
        );
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
            $this->ui->row(
                $this->getHostSelectCol($servers, $default)
                    ->width(12)
            ),
            $this->ui->when($serverAccess, fn() =>
                $this->ui->row(
                    $this->ui->col()
                        ->width(12)
                        ->tbnBindApp(rq(ServerCommand::class))
                )
            ),
            $this->ui->row(
                $this->ui->col()
                    ->width(12)
                    ->tbnBindApp(rq(MenuDatabases::class))
            ),
            $this->ui->row(
                $this->ui->col()
                    ->width(12)
                    ->tbnBindApp(rq(MenuSchemas::class))
            ),
            $this->ui->row(
                $this->ui->col()
                    ->width(12)
                    ->tbnBindApp(rq(DatabaseCommand::class))
            ),
            $this->ui->row(
                $this->ui->col()
                    ->width(12)
                    ->tbnBindApp(rq(MenuSections::class))
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
    public function wrapper(): string
    {
        return $this->ui->build(
            $this->ui->div(
                $this->ui->div(
                    $this->ui->div(
                        $this->ui->div()->tbnBindApp(rq(Breadcrumbs::class))
                    )->setStyle('flex: 1'),
                    $this->ui->div(
                        $this->ui->div()->tbnBindApp(rq(PageActions::class))
                    )->setStyle('padding-left:5px;')
                )->setStyle('display:flex; flex-direction:row; align-items:flex-start; margin-bottom: 10px;'),
                $this->ui->div()
                    ->tbnBindApp(rq(Content::class))
                    ->setClass('jaxon-dbadmin-columns-wrapper page-content-wrapper')
            )->setClass('jaxon-dbadmin-columns-wrapper full-height')
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
                    $this->ui->div()
                        ->jxnBind(rq(AppTabMenu::class))
                        ->setClass('jaxon-dbadmin-tabs-layout_button'),
                    $this->ui->tabs(
                        $this->ui->tabNav(
                            $this->tabNavItem('&nbsp;', true)
                        )->setId('dbadmin-server-tab-nav')
                    )->content($contentId)
                        ->setClass('jaxon-dbadmin-tabs-layout_header')
                )->setClass('jaxon-dbadmin-tabs-layout'),
                $this->ui->tabContent(
                    $this->tabContentItem(true)
                )->setId($contentId)
                    ->addClass('full-height')
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
                        $this->ui->div(
                            cl(AuditSidebar::class)->html()
                        )->jxnBind(rq(AuditSidebar::class))
                    )->setClass('jaxon-dbadmin-content-layout_sidebar'),
                    $this->ui->div(
                        $this->ui->div(
                            cl(AuditWrapper::class)->html()
                        )->jxnBind(rq(AuditWrapper::class))
                    )->setClass('jaxon-dbadmin-content-layout_wrapper')
                )->setClass('jaxon-dbadmin-content-layout')
            )->setId('jaxon-dbadmin')
        );
    }
}
