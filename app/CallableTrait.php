<?php

namespace Lagdo\DbAdmin\App;

use Jaxon\App\View\Store;
use Lagdo\DbAdmin\App\Ajax\Page\Breadcrumbs;

use function call_user_func_array;
use function func_get_args;

/**
 * Callable base class
 *
 * @databag dbadmin
 */
trait CallableTrait
{
    /**
     * Get a translated string
     * The first parameter is mandatory. Optional parameters can follow.
     *
     * @param string $phrase
     *
     * @return string
     */
    protected function lang($phrase): string
    {
        return call_user_func_array([$this->trans, "lang"], func_get_args());
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
        if(!$this->package->getOption('debug.queries', false))
        {
            return;
        }
        foreach($this->db->queries() as $query)
        {
            $this->response->debug($query['start'] . ' => ' . $query['query']);
        }
    }
}
