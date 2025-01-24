<?php

namespace Lagdo\DbAdmin\App;

use Jaxon\App\View\Store;
use Lagdo\DbAdmin\App\Ajax\Page\Breadcrumbs;
use Lagdo\DbAdmin\Db\DbFacade;

/**
 * Callable base class
 *
 * @databag dbadmin
 */
trait CallableTrait
{
    /**
     * @return DbFacade
     */
    abstract protected function db(): DbFacade;

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
