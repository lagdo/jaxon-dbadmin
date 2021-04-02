<?php

namespace Lagdo\Adminer;

use Jaxon\CallableClass;
use Jaxon\Utils\View\Store;

/**
 * Callable base class
 */
class AdminerCallable extends CallableClass
{
    /**
     * The Jaxon Adminer package
     *
     * @var Package
     */
    protected $package;

    /**
     * The proxy to Adminer functions
     *
     * @var DbProxy
     */
    protected $dbProxy;

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
        return $this->view()->render('adminer::views::' . $sViewName, $aViewData);
    }

    /**
     * Show breadcrumbs
     *
     * @return void
     */
    protected function showBreadcrumbs()
    {
        $content = $this->render('main/breadcrumbs', [
            'breadcrumbs' => $this->dbProxy->getBreadcrumbs(),
        ]);
        $this->response->html($this->package->getBreadcrumbsId(), $content);
        $this->response->html($this->package->getDbActionsId(), $this->render('main/actions'));
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
        $serverAccess = $this->package->getConfig()->getOption("servers.$server.access.server", null);
        if($serverAccess === true ||
            ($serverAccess === null && $this->package->getConfig()->getOption('access.server', true)))
        {
            return true;
        }
        if($showError)
        {
            $this->response->dialog->warning('Access to server data is forbidden');
        }
        return false;
    }
}
