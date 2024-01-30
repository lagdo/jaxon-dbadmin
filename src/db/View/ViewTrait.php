<?php

namespace Lagdo\DbAdmin\Db\View;

use Lagdo\DbAdmin\Db\AbstractFacade;
use Exception;

/**
 * Facade to view functions
 */
trait ViewTrait
{
    /**
     * The proxy
     *
     * @var ViewFacade
     */
    protected $viewFacade = null;

    /**
     * @return AbstractFacade
     */
    abstract public function facade(): AbstractFacade;

    /**
     * Connect to a database server
     *
     * @param string $server    The selected server
     * @param string $database  The database name
     * @param string $schema    The database schema
     *
     * @return void
     */
    abstract public function connect(string $server, string $database = '', string $schema = '');

    /**
     * Set the breadcrumbs items
     *
     * @param array $breadcrumbs
     *
     * @return void
     */
    abstract protected function setBreadcrumbs(array $breadcrumbs);

    /**
     * Get the proxy
     *
     * @return ViewFacade
     */
    protected function view(): ViewFacade
    {
        if (!$this->viewFacade) {
            $this->viewFacade = new ViewFacade();
            $this->viewFacade->init($this->facade());
        }
        return $this->viewFacade ;
    }

    /**
     * Get details about a view
     *
     * @param string $server    The selected server
     * @param string $database  The database name
     * @param string $schema    The database schema
     * @param string $view      The view name
     *
     * @return array
     */
    public function getViewInfo(string $server, string $database, string $schema, string $view): array
    {
        $this->connect($server, $database, $schema);

        $package = $this->facade()->package;
        $this->setBreadcrumbs([$package->getServerName($server), $database, $this->trans->lang('Views'), $view]);

        $this->util->input()->table = $view;
        return $this->view()->getViewInfo($view);
    }

    /**
     * Get details about a view
     *
     * @param string $server The selected server
     * @param string $database The database name
     * @param string $schema The database schema
     * @param string $view The view name
     *
     * @return array
     * @throws Exception
     */
    public function getViewFields(string $server, string $database, string $schema, string $view): array
    {
        $this->connect($server, $database, $schema);
        $this->util->input()->table = $view;
        return $this->view()->getViewFields($view);
    }

    /**
     * Get the triggers of a view
     *
     * @param string $server    The selected server
     * @param string $database  The database name
     * @param string $schema    The database schema
     * @param string $view      The view name
     *
     * @return array|null
     */
    public function getViewTriggers(string $server, string $database, string $schema, string $view): ?array
    {
        $this->connect($server, $database, $schema);
        $this->util->input()->table = $view;
        return $this->view()->getViewTriggers($view);
    }

    /**
     * Get a view
     *
     * @param string $server The selected server
     * @param string $database The database name
     * @param string $schema The database schema
     * @param string $view The view name
     *
     * @return array
     * @throws Exception
     */
    public function getView(string $server, string $database, string $schema, string $view): array
    {
        $this->connect($server, $database, $schema);
        $this->util->input()->table = $view;
        return $this->view()->getView($view);
    }

    /**
     * Create a view
     *
     * @param string $server The selected server
     * @param string $database The database name
     * @param string $schema The database schema
     * @param array $values The view values
     *
     * @return array
     * @throws Exception
     */
    public function createView(string $server, string $database, string $schema, array $values): array
    {
        $this->connect($server, $database, $schema);
        $this->util->input()->table = $values['name'];
        return $this->view()->createView($values);
    }

    /**
     * Update a view
     *
     * @param string $server The selected server
     * @param string $database The database name
     * @param string $schema The database schema
     * @param string $view The view name
     * @param array $values The view values
     *
     * @return array
     * @throws Exception
     */
    public function updateView(string $server, string $database, string $schema, string $view, array $values): array
    {
        $this->connect($server, $database, $schema);
        $this->util->input()->table = $view;
        return $this->view()->updateView($view, $values);
    }

    /**
     * Drop a view
     *
     * @param string $server The selected server
     * @param string $database The database name
     * @param string $schema The database schema
     * @param string $view The view name
     *
     * @return array
     * @throws Exception
     */
    public function dropView(string $server, string $database, string $schema, string $view): array
    {
        $this->connect($server, $database, $schema);
        $this->util->input()->table = $view;
        return $this->view()->dropView($view);
    }
}
