<?php

namespace Lagdo\DbAdmin\Db\Driver\Proxy;

use Exception;

/**
 * Proxy to view functions
 */
trait ViewTrait
{
    use AbstractTrait;

    /**
     * Get the proxy
     *
     * @return ViewProxy
     */
    protected function viewProxy(): ViewProxy
    {
        return $this->di()->g(ViewProxy::class);
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
        $this->breadcrumbs(true)
            ->item($this->utils()->lang('Views'))
            ->item("<i><b>$view</b></i>");
        $this->utils()->input->table = $view;
        return $this->viewProxy()->getViewInfo($view);
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
        $this->utils()->input->table = $view;
        return $this->viewProxy()->getViewFields($view);
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
        $this->utils()->input->table = $view;
        return $this->viewProxy()->getViewTriggers($view);
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
        $this->utils()->input->table = $view;
        return $this->viewProxy()->getView($view);
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
        $this->utils()->input->table = $values['name'];
        return $this->viewProxy()->createView($values);
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
        $this->utils()->input->table = $view;
        return $this->viewProxy()->updateView($view, $values);
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
        return $this->viewProxy()->dropView($view);
    }
}
