<?php

namespace Lagdo\DbAdmin\Db\View;

use Jaxon\Di\Container;
use Exception;

/**
 * Facade to view functions
 */
trait ViewTrait
{
    /**
     * @return Container
     */
    abstract public function di(): Container;

    /**
     * Connect to a database server
     *
     * @return void
     */
    abstract public function connectToSchema();

    /**
     * Set the breadcrumbs items
     *
     * @param bool $showDatabase
     * @param array $breadcrumbs
     *
     * @return void
     */
    abstract protected function setBreadcrumbs(bool $showDatabase = false, array $breadcrumbs = []);

    /**
     * Get the proxy
     *
     * @return ViewFacade
     */
    protected function view(): ViewFacade
    {
        return $this->di()->g(ViewFacade::class);
    }

    /**
     * Get details about a view
     *
     * @param string $view      The view name
     *
     * @return array
     */
    public function getViewInfo(string $view): array
    {
        $this->connectToSchema();

        $this->setBreadcrumbs(true, [$this->trans->lang('Views'), $view]);

        $this->util->input()->table = $view;
        return $this->view()->getViewInfo($view);
    }

    /**
     * Get details about a view
     *
     * @param string $view The view name
     *
     * @return array
     * @throws Exception
     */
    public function getViewFields(string $view): array
    {
        $this->connectToSchema();
        $this->util->input()->table = $view;
        return $this->view()->getViewFields($view);
    }

    /**
     * Get the triggers of a view
     *
     * @param string $view      The view name
     *
     * @return array|null
     */
    public function getViewTriggers(string $view): ?array
    {
        $this->connectToSchema();
        $this->util->input()->table = $view;
        return $this->view()->getViewTriggers($view);
    }

    /**
     * Get a view
     *
     * @param string $view The view name
     *
     * @return array
     * @throws Exception
     */
    public function getView(string $view): array
    {
        $this->connectToSchema();
        $this->util->input()->table = $view;
        return $this->view()->getView($view);
    }

    /**
     * Create a view
     *
     * @param array $values The view values
     *
     * @return array
     * @throws Exception
     */
    public function createView(array $values): array
    {
        $this->connectToSchema();
        $this->util->input()->table = $values['name'];
        return $this->view()->createView($values);
    }

    /**
     * Update a view
     *
     * @param string $view The view name
     * @param array $values The view values
     *
     * @return array
     * @throws Exception
     */
    public function updateView(string $view, array $values): array
    {
        $this->connectToSchema();
        $this->util->input()->table = $view;
        return $this->view()->updateView($view, $values);
    }

    /**
     * Drop a view
     *
     * @param string $view The view name
     *
     * @return array
     * @throws Exception
     */
    public function dropView(string $view): array
    {
        $this->connectToSchema();
        $this->util->input()->table = $view;
        return $this->view()->dropView($view);
    }
}
