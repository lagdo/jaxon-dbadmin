<?php

namespace Lagdo\DbAdmin\Ajax;

use Jaxon\App\View\Store;
use Lagdo\DbAdmin\Ajax\App\Page\Breadcrumbs;
use Lagdo\DbAdmin\Db\DbFacade;
use Lagdo\DbAdmin\Package;
use Lagdo\DbAdmin\Translator;
use Lagdo\DbAdmin\Ui\UiBuilder;

/**
 * Callable base class
 *
 * @databag dbadmin
 */
trait CallableTrait
{
    /**
     * @return Package
     */
    protected function package(): Package
    {
        return $this->package;
    }

    /**
     * @return DbFacade
     */
    protected function db(): DbFacade
    {
        return $this->db;
    }

    /**
     * @return UiBuilder
     */
    protected function ui(): UiBuilder
    {
        return $this->ui;
    }

    /**
     * @return Translator
     */
    protected function trans(): Translator
    {
        return $this->trans;
    }

    /**
     * Render a view
     *
     * @param string        $sViewName        The view name
     * @param array         $aViewData        The view data
     *
     * @return null|Store   A store populated with the view data
     */
    protected function renderView($sViewName, array $aViewData = [])
    {
        return $this->view()->render('adminer::templates::' . $sViewName, $aViewData);
    }

    /**
     * Render the main/content view
     *
     * @param array         $aViewData        The view data
     *
     * @return null|Store   A store populated with the view data
     */
    protected function renderMainContent(array $aViewData = [])
    {
        return $this->view()->render('adminer::views::main/content', $aViewData);
    }

    /**
     * Show breadcrumbs
     *
     * @return void
     */
    protected function showBreadcrumbs()
    {
        $this->cl(Breadcrumbs::class)->render();
    }

    /**
     * Print the executed queries in the debug console
     *
     * @return void
     */
    protected function debugQueries()
    {
        if(!$this->package()->getOption('debug.queries', false))
        {
            return;
        }
        foreach($this->db()->queries() as $query)
        {
            $this->response->debug($query['start'] . ' => ' . $query['query']);
        }
    }
}
