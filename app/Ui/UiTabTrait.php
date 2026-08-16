<?php

namespace Lagdo\DbAdmin\App\Ui;

use Lagdo\DbAdmin\App\Ajax\Admin\Content as AdminContent;
use Lagdo\DbAdmin\App\Ajax\Admin\Page\AppUser;
use Lagdo\DbAdmin\App\Ajax\Admin\Page\Breadcrumbs;
use Lagdo\DbAdmin\App\Ajax\Admin\Page\DbServer;
use Lagdo\DbAdmin\App\Ajax\Admin\Page\PageActions;
use Lagdo\DbAdmin\App\Ajax\Admin\Sidebar as AdminSidebar;
use Lagdo\DbAdmin\App\Ui\Tab\Tab;
use Lagdo\UiBuilder\HtmlComponent;

use function Jaxon\cl;
use function Jaxon\jo;
use function Jaxon\pm;
use function Jaxon\rq;

trait UiTabTrait
{
    /**
     * @return Tab
     */
    abstract protected function tab(): Tab;

    /**
     * @param string $title
     * @param bool $active
     *
     * @return HtmlComponent
     */
    private function tabNavItem(string $title, bool $active): HtmlComponent
    {
        return $this->ui->tabNavItem($title)
            ->target($this->tab()->app()->wrapperId())
            ->setId($this->tab()->app()->titleId())
            ->active($active)
            ->jxnClick(jo('jaxon.dbadmin')->onAppTabClick($this->tab()->app()->current()));
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
     * @return HtmlComponent
     */
    private function builtWith(): HtmlComponent
    {
        return $this->ui->card(
            $this->ui->cardBody(
                $this->ui->div(
                    'Built on <a style="text-decoration: none;" href="https://github.com/lagdo/dbadmin-mono" ' .
                        'target="_blank"><i class="fa-brands fa-github"></i></a> with the &middot; ' .
                        '<a style="text-decoration: none;" href="https://www.jaxon-php.org" ' .
                        'target="_blank">Jaxon Ajax library</a> &middot;'
                )
            )
        )->setStyle('margin-top: 10px;');
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
                    cl(AdminContent::class)->html()
                )->setClass('jaxon-dbadmin-main-header_sidebar')
                    ->tbnBindApp(rq(AdminSidebar::class), 'header'),
                $this->ui->div(
                    $this->ui->div(
                        $this->ui->div(
                            $this->ui->div()->tbnBindApp(rq(Breadcrumbs::class))
                        )->setClass('jaxon-dbadmin-server-header-breadcrumbs'),
                        $this->ui->div(
                            $this->ui->div()->tbnBindApp(rq(PageActions::class))
                        )->setClass('jaxon-dbadmin-server-header-actions')
                    )->setClass('jaxon-dbadmin-server-header'),
                )->setClass('jaxon-dbadmin-main-header_content'),
            )->setClass('jaxon-dbadmin-main-header'),
            $this->ui->div(
                $this->ui->div(
                    $this->ui->div(
                        cl(AdminSidebar::class)->html()
                    )->setClass('jaxon-dbadmin-page-sidebar_block')
                        ->tbnBindApp(rq(AdminSidebar::class), 'content'),
                    $this->ui->div('&nbsp;')
                        ->setClass('jaxon-dbadmin-page-sidebar_spacer'),
                    $this->ui->div(
                        $this->ui->div()
                            ->tbnBindApp(rq(DbServer::class)),
                        $this->ui->div()
                            ->tbnBindApp(rq(AppUser::class)),
                        $this->ui->div($this->builtWith())
                    )->setClass('jaxon-dbadmin-page-sidebar_block')
                )->setClass('jaxon-dbadmin-page-sidebar'),
                $this->ui->div(
                    $this->ui->div(
                        cl(AdminContent::class)->html()
                    )->tbnBindApp(rq(AdminContent::class))
                )->setClass('jaxon-dbadmin-page-content')
            )->setClass('jaxon-dbadmin-page-wrapper')
        )->setId($this->tab()->app()->wrapperId())
            ->active($active);
    }

    /**
     * @return string
     */
    public function tabContentItemHtml(): string
    {
        return $this->ui->build(
            $this->tabContentItem(false)
        );
    }

    /**
     * @return string
     */
    private function tabTitleFormId(): string
    {
        return $this->tab()->app()->id('jaxon-dbadmin-app-tab-title');
    }

    /**
     * @return array
     */
    public function tabTitleFormValues(): array
    {
        return pm()->form($this->tabTitleFormId());
    }

    /**
     * @param string $title
     *
     * @return string
     */
    public function editTabTitle(string $title): string
    {
        $label = $this->trans->lang('Title (max 20 chars)');
        return $this->ui->build(
            $this->ui->form(
                $this->ui->row(
                    $this->ui->col(
                        $this->ui->label($this->ui->text( $label))
                            ->setFor('title'),
                    )->width(4),
                    $this->ui->col(
                        $this->ui->input()
                            ->setType('text')
                            ->setName('title')
                            ->setValue($title),
                    )->width(8)
                )
            )->setId($this->tabTitleFormId())
        );
    }
}
