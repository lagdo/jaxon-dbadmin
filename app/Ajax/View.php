<?php

namespace Lagdo\DbAdmin\App\Ajax;

use Jaxon\Response\Response;
use Lagdo\DbAdmin\App\CallableClass;

use Exception;

use function Jaxon\pm;

class View extends CallableClass
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
        // Make data available to views
        $this->view()->shareValues($viewData);

        $content = $this->uiBuilder->mainContent($this->renderMainContent());
        $this->response->html($tabId, $content);
    }

    /**
     * Show detailed info of a given view
     *
     * @after('call' => 'showBreadcrumbs')
     *
     * @param string $server      The database server
     * @param string $database    The database name
     * @param string $schema      The database schema
     * @param string $view        The view name
     *
     * @return Response
     */
    public function show(string $server, string $database, string $schema, string $view): Response
    {
        $viewInfo = $this->dbAdmin->getViewInfo($server, $database, $schema, $view);
        // Make view info available to views
        $this->view()->shareValues($viewInfo);

        // Set main menu buttons
        $content = isset($viewInfo['mainActions']) ?
            $this->uiBuilder->mainActions($viewInfo['mainActions']) : '';
        $this->response->html($this->package->getMainActionsId(), $content);

        $content = $this->uiBuilder->mainDbTable($viewInfo['tabs']);
        $this->response->html($this->package->getDbContentId(), $content);

        // Show fields
        $fieldsInfo = $this->dbAdmin->getViewFields($server, $database, $schema, $view);
        $this->showTab($fieldsInfo, 'tab-content-fields');

        // Show triggers
        $triggersInfo = $this->dbAdmin->getViewTriggers($server, $database, $schema, $view);
        if(\is_array($triggersInfo))
        {
            $this->showTab($triggersInfo, 'tab-content-triggers');
        }

        // Set onclick handlers on toolbar buttons
        $this->jq('#adminer-main-action-edit-view')
            ->click($this->rq()->edit($server, $database, $schema, $view));
        $this->jq('#adminer-main-action-drop-view')
            ->click($this->rq()->drop($server, $database, $schema, $view)
            ->confirm("Drop view $view?"));

        return $this->response;
    }

    /**
     * Show the new view form
     *
     * @param string $server      The database server
     * @param string $database    The database name
     * @param string $schema      The database schema
     *
     * @return Response
     */
    public function add(string $server, string $database, string $schema): Response
    {
        $this->dbAdmin->connect($server);
        $formId = 'view-form';
        $title = 'Create a view';
        $materializedView = $this->dbAdmin->driver->support('materializedview');
        $content = $this->uiBuilder->viewForm($formId, $materializedView);
        $buttons = [[
            'title' => 'Cancel',
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ],[
            'title' => 'Save',
            'class' => 'btn btn-primary',
            'click' => $this->rq()->create($server, $database, $schema, pm()->form($formId)),
        ]];
        $this->response->dialog->show($title, $content, $buttons);

        return $this->response;
    }

    /**
     * Show edit form for a given view
     *
     * @param string $server      The database server
     * @param string $database    The database name
     * @param string $schema      The database schema
     * @param string $view        The view name
     *
     * @return Response
     */
    public function edit(string $server, string $database, string $schema, string $view): Response
    {
        $viewData = $this->dbAdmin->getView($server, $database, $schema, $view);
        // Make view info available to views
        $this->view()->shareValues($viewData);

        $formId = 'view-form';
        $title = 'Edit a view';
        $materializedView = $this->dbAdmin->driver->support('materializedview');
        $content = $this->uiBuilder->viewForm($formId, $materializedView, $viewData['view']);
        $buttons = [[
            'title' => 'Cancel',
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ],[
            'title' => 'Save',
            'class' => 'btn btn-primary',
            'click' => $this->rq()->update($server, $database, $schema, $view, pm()->form($formId)),
        ]];
        $this->response->dialog->show($title, $content, $buttons);

        return $this->response;
    }

    /**
     * Create a new view
     *
     * @param string $server      The database server
     * @param string $database    The database name
     * @param string $schema      The database schema
     * @param array $values      The view values
     *
     * @return Response
     */
    public function create(string $server, string $database, string $schema, array $values): Response
    {
        $values['materialized'] = \array_key_exists('materialized', $values);

        $result = $this->dbAdmin->createView($server, $database, $schema, $values);
        if(!$result['success'])
        {
            $this->response->dialog->error($result['error']);
            return $this->response;
        }

        $this->response->dialog->hide();
        $this->cl(Database::class)->showViews($server, $database, $schema);
        $this->response->dialog->success($result['message']);
        return $this->response;
    }

    /**
     * Update a given view
     *
     * @param string $server      The database server
     * @param string $database    The database name
     * @param string $schema      The database schema
     * @param string $view        The view name
     * @param array $values      The view values
     *
     * @return Response
     */
    public function update(string $server, string $database, string $schema, string $view, array $values): Response
    {
        $values['materialized'] = \array_key_exists('materialized', $values);

        $result = $this->dbAdmin->updateView($server, $database, $schema, $view, $values);
        if(!$result['success'])
        {
            $this->response->dialog->error($result['error']);
            return $this->response;
        }

        $this->response->dialog->hide();
        $this->cl(Database::class)->showViews($server, $database, $schema);
        $this->response->dialog->success($result['message']);
        return $this->response;
    }

    /**
     * Drop a given view
     *
     * @param string $server      The database server
     * @param string $database    The database name
     * @param string $schema      The database schema
     * @param string $view        The view name
     *
     * @return Response
     */
    public function drop(string $server, string $database, string $schema, string $view): Response
    {
        $result = $this->dbAdmin->dropView($server, $database, $schema, $view);
        if(!$result['success'])
        {
            $this->response->dialog->error($result['error']);
            return $this->response;
        }

        $this->cl(Database::class)->showViews($server, $database, $schema);
        $this->response->dialog->success($result['message']);
        return $this->response;
    }
}
