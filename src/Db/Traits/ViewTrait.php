<?php

namespace Lagdo\DbAdmin\Db\Traits;

use Exception;
use Lagdo\DbAdmin\Db\Facades\ViewFacade;

/**
 * Facade to view functions
 */
trait ViewTrait
{
    use AbstractTrait;

    /**
     * Get the facade
     *
     * @return ViewFacade
     */
    protected function viewFacade(): ViewFacade
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
        $this->breadcrumbs(true)
            ->item($this->utils->trans->lang('Views'))
            ->item("<i><b>$view</b></i>");
        $this->utils->input->table = $view;
        return $this->viewFacade()->getViewInfo($view);
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
        $this->utils->input->table = $view;
        return $this->viewFacade()->getViewFields($view);
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
        $this->utils->input->table = $view;
        return $this->viewFacade()->getViewTriggers($view);
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
        $this->utils->input->table = $view;
        return $this->viewFacade()->getView($view);
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
        $this->utils->input->table = $values['name'];
        return $this->viewFacade()->createView($values);
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
        $this->utils->input->table = $view;
        return $this->viewFacade()->updateView($view, $values);
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
        $this->utils->input->table = $view;
        return $this->viewFacade()->dropView($view);
    }
}
