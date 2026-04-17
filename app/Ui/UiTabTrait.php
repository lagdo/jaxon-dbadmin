<?php

namespace Lagdo\DbAdmin\App\Ui;

use Lagdo\DbAdmin\App\Ajax\Admin\Page\DbServer;
use Lagdo\DbAdmin\App\Ajax\Admin\Page\DbUser;
use Lagdo\DbAdmin\App\Ajax\Admin\Sidebar as AdminSidebar;
use Lagdo\DbAdmin\App\Ajax\Admin\Wrapper as AdminWrapper;
use Lagdo\DbAdmin\App\Ui\Tab\Tab;
use Lagdo\UiBuilder\Component\HtmlComponent;

use function Jaxon\cl;
use function Jaxon\form;
use function Jaxon\jo;
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
     * @param bool $active
     *
     * @return HtmlComponent
     */
    private function tabContentItem(bool $active): HtmlComponent
    {
        return $this->ui->tabContentItem(
            $this->ui->div(
                $this->ui->div(
                    $this->ui->row()->tbnBindApp(rq(DbUser::class)),
                    $this->ui->div(
                        cl(AdminSidebar::class)->html()
                    )->tbnBindApp(rq(AdminSidebar::class)),
                    $this->ui->row()->tbnBindApp(rq(DbServer::class))
                )->setClass('jaxon-dbadmin-content-layout_sidebar'),
                $this->ui->div(
                    $this->ui->div(
                        cl(AdminWrapper::class)->html()
                    )->tbnBindApp(rq(AdminWrapper::class))
                )->setClass('jaxon-dbadmin-content-layout_wrapper')
            )->setClass('jaxon-dbadmin-content-layout')
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
        return form($this->tabTitleFormId());
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
