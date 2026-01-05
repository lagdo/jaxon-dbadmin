<?php

namespace Lagdo\DbAdmin\Ui;

use Lagdo\DbAdmin\Ajax\Admin\Admin;
use Lagdo\DbAdmin\Ajax\Admin\Menu\Sections as MenuSections;
use Lagdo\DbAdmin\Ajax\Admin\Menu\Database\Command as DatabaseCommand;
use Lagdo\DbAdmin\Ajax\Admin\Menu\Database\Schemas as MenuSchemas;
use Lagdo\DbAdmin\Ajax\Admin\Menu\Server\Command as ServerCommand;
use Lagdo\DbAdmin\Ajax\Admin\Menu\Server\Databases as MenuDatabases;
use Lagdo\DbAdmin\Ajax\Admin\Page\Breadcrumbs;
use Lagdo\DbAdmin\Ajax\Admin\Page\Content;
use Lagdo\DbAdmin\Ajax\Admin\Page\PageActions;
use Lagdo\DbAdmin\Ajax\Admin\Page\DbConnection;
use Lagdo\DbAdmin\Db\Translator;
use Lagdo\UiBuilder\BuilderInterface;

use function count;
use function array_shift;
use function Jaxon\je;
use function Jaxon\rq;

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
                    )->setId('jaxon-dbadmin-dbhost-select'),
                    $this->ui->button($this->ui->text('Show'))
                        ->primary()
                        ->setClass('btn-select')
                        ->jxnClick(rq(Admin::class)
                            ->server(je('jaxon-dbadmin-dbhost-select')->rd()->select()))
                )
            )
        );
    }

    /**
     * @param array $servers
     * @param bool $serverAccess
     * @param string $default
     *
     * @return mixed
     */
    private function sidebarContent(array $servers, bool $serverAccess, string $default): mixed
    {
        return $this->ui->list(
            $this->ui->row(
                $this->getHostSelectCol($servers, $default)
                    ->width(12)
            ),
            $this->ui->when($serverAccess, fn() =>
                $this->ui->row(
                    $this->ui->col()
                        ->width(12)
                        ->jxnBind(rq(ServerCommand::class))
                )
            ),
            $this->ui->row(
                $this->ui->col()
                    ->width(12)
                    ->jxnBind(rq(MenuDatabases::class))
            ),
            $this->ui->row(
                $this->ui->col()
                    ->width(12)
                    ->jxnBind(rq(MenuSchemas::class))
            ),
            $this->ui->row(
                $this->ui->col()
                    ->width(12)
                    ->jxnBind(rq(DatabaseCommand::class))
            ),
            $this->ui->row(
                $this->ui->col()
                    ->width(12)
                    ->jxnBind(rq(MenuSections::class))
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
            $this->sidebarContent($servers, $serverAccess, $default)
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
     * @return mixed
     */
    private function wrapperContent(): mixed
    {
        return $this->ui->list(
            $this->ui->row()->jxnBind(rq(DbConnection::class)),
            $this->ui->row(
                $this->ui->col(
                    $this->ui->span(['style' => 'float:left'])
                        ->jxnBind(rq(Breadcrumbs::class)),
                    $this->ui->span(['style' => 'float:right'])
                        ->jxnBind(rq(PageActions::class))
                )->width(12)
            ),
            $this->ui->row(
                $this->ui->col()
                    ->width(12)
                    ->jxnBind(rq(Content::class))
            )
        );
    }

    /**
     * @return string
     */
    public function wrapper(): string
    {
        return $this->ui->build(
            $this->wrapperContent()
        );
    }

    /**
     * @param array $servers
     * @param bool $serverAccess
     * @param string $default
     *
     * @return string
     */
    public function home(array $servers, bool $serverAccess, string $default): string
    {
        return $this->ui->build(
            $this->ui->row(
                $this->ui->col(
                    $this->sidebarContent($servers, $serverAccess, $default)
                )->width(3),
                $this->ui->col(
                    $this->wrapperContent()
                )->width(9)
            )->setId('jaxon-dbadmin')
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
}
