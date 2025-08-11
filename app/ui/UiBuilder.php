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
use Lagdo\DbAdmin\Translator;
use Lagdo\UiBuilder\BuilderInterface;

use function Jaxon\pm;
use function Jaxon\rq;

class UiBuilder
{
    use PageTrait;

    /**
     * @param Translator $trans
     * @param BuilderInterface $html
     */
    public function __construct(protected Translator $trans, protected BuilderInterface $html)
    {}

    /**
     * @return BuilderInterface
     */
    protected function builder(): BuilderInterface
    {
        return $this->html;
    }

    /**
     * @param array $servers
     * @param string $default
     *
     * @return mixed
     */
    private function getHostSelectCol(array $servers, string $default): mixed
    {
        return $this->html->col(
            $this->html->inputGroup(
                $this->html->formSelect(
                    $this->html->each($servers, fn($server, $serverId) =>
                        $this->html->option($server['name'])
                            ->selected($serverId === $default)
                            ->setValue($serverId)
                    )
                )
                ->setId('jaxon-dbadmin-dbhost-select'),
                $this->html->button($this->html->text('Show'))
                    ->primary()->setClass('btn-select')
                    ->jxnClick(rq(Admin::class)
                        ->server(pm()->select('jaxon-dbadmin-dbhost-select')))
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
        return $this->html->list(
            $this->html->row(
                $this->getHostSelectCol($servers, $default)
                    ->width(12)
            ),
            $this->html->when($serverAccess, fn() =>
                $this->html->row(
                    $this->html->col()
                        ->width(12)
                        ->jxnBind(rq(ServerCommand::class))
                )
            ),
            $this->html->row(
                $this->html->col()
                    ->width(12)
                    ->jxnBind(rq(MenuDatabases::class))
            ),
            $this->html->row(
                $this->html->col()
                    ->width(12)
                    ->jxnBind(rq(MenuSchemas::class))
            ),
            $this->html->row(
                $this->html->col()
                    ->width(12)
                    ->jxnBind(rq(DatabaseCommand::class))
            ),
            $this->html->row(
                $this->html->col()
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
        return $this->html->build($this->sidebarContent($servers, $serverAccess, $default));
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
                    $this->html->breadcrumbItem($this->html->text($breadcrumb))
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
        return $this->html->build(
            $this->html->buttonGroup(
                $this->html->each($actions, fn($action, $class) =>
                    $this->html->button(['class' => $class],
                        $this->html->text($action['title']))
                        ->outline()->secondary()
                        ->jxnClick($action['handler'])
                )
            )
            ->setClass('dbadmin-main-action-group')
        );
    }

    /**
     * @return mixed
     */
    private function wrapperContent(): mixed
    {
        return $this->html->list(
            $this->html->row()->jxnBind(rq(ServerInfo::class)),
            $this->html->row(
                $this->html->col(
                    $this->html->span(['style' => 'float:left'])
                        ->jxnBind(rq(Breadcrumbs::class)),
                    $this->html->span(['style' => 'float:right'])
                        ->jxnBind(rq(PageActions::class))
                )
                ->width(12)
            ),
            $this->html->row(
                $this->html->col()
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
        return $this->html->build(
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
        return $this->html->build(
            $this->html->row(
                $this->html->col(
                    $this->sidebarContent($servers, $serverAccess, $default)
                )
                ->width(3),
                $this->html->col(
                    $this->wrapperContent()
                )
                ->width(9)
            )
            ->setId('jaxon-dbadmin')
        );
    }
}
