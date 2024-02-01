<?php

namespace Lagdo\DbAdmin\App;

use Jaxon\App\CallableClass as JaxonCallableClass;
use Jaxon\App\View\Store;
use Lagdo\DbAdmin\Db\DbFacade;
use Lagdo\DbAdmin\Ui\UiBuilder;

use function count;

/**
 * Callable base class
 *
 * @databag dbadmin
 * @before selectDatabase
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
     * @var DbFacade
     */
    protected $db;

    /**
     * @var UiBuilder
     */
    protected $ui;

    /**
     * The constructor
     *
     * @param Package       $package    The DbAdmin package
     * @param DbFacade      $db         The facade to database functions
     * @param UiBuilder     $ui         The HTML UI builder
     */
    public function __construct(Package $package, DbFacade $db, UiBuilder $ui)
    {
        $this->package = $package;
        $this->db = $db;
        $this->ui = $ui;
    }

    /**
     * Set the current database
     *
     * @return void
     */
    protected function selectDatabase()
    {
        $server = $database = $schema = '';
        $db = $this->bag('dbadmin')->get('db', []);
        if(count($db) > 0)
        {
            $server = $db[0];
        }
        if(count($db) > 1)
        {
            $database = $db[1];
        }
        if(count($db) > 2)
        {
            $schema = $db[2];
        }
        $this->db->selectDatabase($server, $database, $schema);
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
        $content = $this->ui->breadcrumbs($this->db->getBreadcrumbs());
        $this->response->html($this->package->getBreadcrumbsId(), $content);
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
        $this->jq('.adminer-menu-item', '#' . $this->package->getServerActionsId())->removeClass('active');
        $this->jq('.adminer-menu-item', '#' . $this->package->getDbActionsId())->removeClass('active');
        $this->jq('.adminer-menu-item', '#' . $this->package->getDbMenuId())->removeClass('active');
        $this->jq($menuId, '#' . $wrapperId)->addClass('active');
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
