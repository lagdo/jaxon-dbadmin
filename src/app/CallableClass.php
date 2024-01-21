<?php

namespace Lagdo\DbAdmin\App;

use Jaxon\App\CallableClass as JaxonCallableClass;
use Jaxon\Utils\View\Store;

use Lagdo\DbAdmin\DbAdmin;
use Lagdo\DbAdmin\Ui\Builder;

/**
 * Callable base class
 */
class CallableClass extends JaxonCallableClass
{
    /**
     * The Jaxon DbAdmin package
     *
     * @var Package
     */
    protected $package;

    /**
     * The facade to database functions
     *
     * @var DbAdmin
     */
    protected $dbAdmin;

    /**
     * @var Builder
     */
    protected $uiBuilder;

    /**
     * The constructor
     *
     * @param Package $package    The DbAdmin package
     * @param DbAdmin $dbAdmin    The facade to database functions
     * @param Builder $uiBuilder  The HTML UI builder
     */
    public function __construct(Package $package, DbAdmin $dbAdmin, Builder $uiBuilder)
    {
        $this->package = $package;
        $this->dbAdmin = $dbAdmin;
        $this->uiBuilder = $uiBuilder;
    }

    /**
     * Render a view
     *
     * @param string        $sViewName        The view name
     * @param array         $aViewData        The view data
     *
     * @return null|Store   A store populated with the view data
     */
    protected function render($sViewName, array $aViewData = [])
    {
        return $this->view()->render('adminer::templates::' . $sViewName, $aViewData);
    }

    /**
     * Render the manin/content view
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
        $content = $this->uiBuilder->breadcrumbs($this->dbAdmin->getBreadcrumbs());
        $this->response->html($this->package->getBreadcrumbsId(), $content);
    }

    /**
     * Check if the user has access to a server
     *
     * @param string $server      The database server
     * @param boolean $showError  Show error message
     *
     * return bool
     */
    protected function checkServerAccess(string $server, $showError = true)
    {
        $serverAccess = $this->package->getOption("servers.$server.access.server", null);
        if($serverAccess === true ||
            ($serverAccess === null && $this->package->getOption('access.server', true)))
        {
            return true;
        }
        if($showError)
        {
            $this->response->dialog->warning('Access to server data is forbidden');
        }
        return false;
    }

    /**
     * Select a menu item
     *
     * @param string $menuId      The selected menu id
     * @param string $wrapperId   The menu item wrapper id
     *
     * return void
     */
    protected function selectMenuItem(string $menuId, string $wrapperId)
    {
        $this->jq('.adminer-menu-item', '#'. $this->package->getServerActionsId())->removeClass('active');
        $this->jq('.adminer-menu-item', '#'. $this->package->getDbActionsId())->removeClass('active');
        $this->jq('.adminer-menu-item', '#'. $this->package->getDbMenuId())->removeClass('active');
        $this->jq($menuId, '#'. $wrapperId)->addClass('active');
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
        foreach($this->dbAdmin->queries() as $query)
        {
            $this->response->debug($query['start'] . ' => ' . $query['query']);
        }
    }
}
