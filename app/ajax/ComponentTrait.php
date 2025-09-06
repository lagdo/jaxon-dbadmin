<?php

namespace Lagdo\DbAdmin\Ajax;

use Jaxon\App\View\Store;
use Lagdo\DbAdmin\Ajax\App\Page\Breadcrumbs;
use Lagdo\DbAdmin\Db\DbFacade;
use Lagdo\DbAdmin\Package;
use Lagdo\DbAdmin\Translator;
use Lagdo\DbAdmin\Ui\UiBuilder;
use Exception;

/**
 * Commont functions for component classes
 *
 * @databag dbadmin
 */
trait ComponentTrait
{
    /**
     * @var Package
     */
    protected Package $package;

    /**
     * @var DbFacade
     */
    protected DbFacade $db;

    /**
     * @var UiBuilder
     */
    protected UiBuilder $ui;

    /**
     * @var Translator
     */
    protected Translator $trans;

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
     * @throws Exception
     * @return never
     */
    protected function notYetAvailable(): void
    {
        throw new AppException($this->trans->lang('This feature is not yet available'));
    }

    /**
     * Render a view
     *
     * @param string        $sViewName        The view name
     * @param array         $aViewData        The view data
     *
     * @return null|Store   A store populated with the view data
     */
    protected function renderView($sViewName, array $aViewData = []): null|Store
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
    protected function renderMainContent(array $aViewData = []): null|Store
    {
        return $this->view()->render('adminer::views::main/content', $aViewData);
    }

    /**
     * Show breadcrumbs
     *
     * @return void
     */
    protected function showBreadcrumbs(): void
    {
        $this->cl(Breadcrumbs::class)->render();
    }
}
