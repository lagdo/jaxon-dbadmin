<?php

namespace Lagdo\DbAdmin\App\Ajax\Db;

use Jaxon\Response\Response;
use Lagdo\DbAdmin\App\CallableDbClass;
use Lagdo\DbAdmin\App\Ajax\Page\Content;
use Lagdo\DbAdmin\App\Ajax\Page\PageActions;

use function Jaxon\pm;

class View extends CallableDbClass
{
    /**
     * Display the content of a tab
     *
     * @param array  $viewData  The data to be displayed in the view
     * @param string $tabId     The tab container id
     *
     * @return void
     */
    protected function showTab(array $viewData, string $tabId)
    {
        $this->response->html($tabId, $this->ui->mainContent($viewData));
    }

    /**
     * Show detailed info of a given view
     *
     * @after('call' => 'showBreadcrumbs')
     *
     * @param string $view        The view name
     *
     * @return Response
     */
    public function show(string $view): Response
    {
        $viewInfo = $this->db->getViewInfo($view);
        // Make view info available to views
        $this->view()->shareValues($viewInfo);

        // Set main menu buttons
        $this->cl(PageActions::class)->showView($view);

        $content = $this->ui->mainDbTable($viewInfo['tabs']);
        $this->cl(Content::class)->showHtml($content);

        // Show fields
        $fieldsInfo = $this->db->getViewFields($view);
        $this->showTab($fieldsInfo, 'tab-content-fields');

        // Show triggers
        $triggersInfo = $this->db->getViewTriggers($view);
        if(\is_array($triggersInfo))
        {
            $this->showTab($triggersInfo, 'tab-content-triggers');
        }

        return $this->response;
    }

    /**
     * Show the new view form
     *
     * @return Response
     */
    public function add(): Response
    {
        $formId = 'view-form';
        $title = 'Create a view';
        $materializedView = $this->db->driver->support('materializedview');
        $content = $this->ui->viewForm($formId, $materializedView);
        $buttons = [[
            'title' => 'Cancel',
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ],[
            'title' => 'Save',
            'class' => 'btn btn-primary',
            'click' => $this->rq()->create(pm()->form($formId)),
        ]];
        $this->response->dialog->show($title, $content, $buttons);

        return $this->response;
    }

    /**
     * Create a new view
     *
     * @param array $values      The view values
     *
     * @return Response
     */
    public function create(array $values): Response
    {
        $values['materialized'] = \array_key_exists('materialized', $values);

        $result = $this->db->createView($values);
        if(!$result['success'])
        {
            $this->response->dialog->error($result['error']);
            return $this->response;
        }

        $this->response->dialog->hide();
        $this->cl(Database::class)->showViews();
        $this->response->dialog->success($result['message']);
        return $this->response;
    }

    /**
     * Show edit form for a given view
     *
     * @param string $view        The view name
     *
     * @return Response
     */
    public function edit(string $view): Response
    {
        $viewData = $this->db->getView($view);
        // Make view info available to views
        $this->view()->shareValues($viewData);

        $formId = 'view-form';
        $title = 'Edit a view';
        $materializedView = $this->db->driver->support('materializedview');
        $content = $this->ui->viewForm($formId, $materializedView, $viewData['view']);
        $buttons = [[
            'title' => 'Cancel',
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ],[
            'title' => 'Save',
            'class' => 'btn btn-primary',
            'click' => $this->rq()->update($view, pm()->form($formId)),
        ]];
        $this->response->dialog->show($title, $content, $buttons);

        return $this->response;
    }

    /**
     * Update a given view
     *
     * @param string $view        The view name
     * @param array $values      The view values
     *
     * @return Response
     */
    public function update(string $view, array $values): Response
    {
        $values['materialized'] = \array_key_exists('materialized', $values);

        $result = $this->db->updateView($view, $values);
        if(!$result['success'])
        {
            $this->response->dialog->error($result['error']);
            return $this->response;
        }

        $this->response->dialog->hide();
        $this->cl(Database::class)->showViews();
        $this->response->dialog->success($result['message']);
        return $this->response;
    }

    /**
     * Drop a given view
     *
     * @param string $view        The view name
     *
     * @return Response
     */
    public function drop(string $view): Response
    {
        $result = $this->db->dropView($view);
        if(!$result['success'])
        {
            $this->response->dialog->error($result['error']);
            return $this->response;
        }

        $this->cl(Database::class)->showViews();
        $this->response->dialog->success($result['message']);
        return $this->response;
    }
}
