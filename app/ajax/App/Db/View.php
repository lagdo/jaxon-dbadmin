<?php

namespace Lagdo\DbAdmin\Ajax\App\Db;

use Lagdo\DbAdmin\Ajax\CallableDbClass;
use Lagdo\DbAdmin\Ajax\App\Page\Content;
use Lagdo\DbAdmin\Ajax\App\Page\PageActions;

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
        $this->response->html($tabId, $this->ui()->mainContent($viewData));
    }

    /**
     * Print links after select heading
     * Copied from selectLinks() in adminer.inc.php
     *
     * @param bool $new New item options, NULL for no new item
     *
     * @return array
     */
    // protected function getViewLinks(bool $new = false): array
    // {
    //     $links = [
    //         'select' => $this->trans()->lang('Select data'),
    //     ];
    //     if ($this->db()->support('indexes')) {
    //         $links['table'] = $this->trans()->lang('Show structure');
    //     }
    //     if ($this->db()->support('table')) {
    //         $links['table'] = $this->trans()->lang('Show structure');
    //         $links['alter'] = $this->trans()->lang('Alter view');
    //     }
    //     if ($new) {
    //         $links['edit'] = $this->trans()->lang('New item');
    //     }
    //     // $links['docs'] = \doc_link([$this->db()->jush() => $this->db()->tableHelp($name)], '?');

    //     return $links;
    // }

    /**
     * Show detailed info of a given view
     *
     * @after showBreadcrumbs
     *
     * @param string $view        The view name
     *
     * @return void
     */
    public function show(string $view)
    {
        $viewInfo = $this->db()->getViewInfo($view);
        // Make view info available to views
        $this->view()->shareValues($viewInfo);

        // Set main menu buttons
        // $actions = [
        //     $this->trans()->lang('Add trigger'),
        // ];

        // $actions = $this->getViewLinks();

        $actions = [
            'edit-view' => [
                'title' => $this->trans()->lang('Edit view'),
                'handler' => $this->rq()->edit($view),
            ],
            'drop-view' => [
                'title' => $this->trans()->lang('Drop view'),
                'handler' => $this->rq()->drop($view)->confirm("Drop view $view?"),
            ],
        ];
        $this->cl(PageActions::class)->show($actions);

        $content = $this->ui()->mainDbTable($viewInfo['tabs']);
        $this->cl(Content::class)->showHtml($content);

        // Show fields
        $fieldsInfo = $this->db()->getViewFields($view);
        $this->showTab($fieldsInfo, 'tab-content-fields');

        // Show triggers
        $triggersInfo = $this->db()->getViewTriggers($view);
        if(\is_array($triggersInfo))
        {
            $this->showTab($triggersInfo, 'tab-content-triggers');
        }
    }

    /**
     * Show the new view form
     *
     * @return void
     */
    public function add()
    {
        $formId = 'view-form';
        $title = 'Create a view';
        $materializedView = $this->db()->support('materializedview');
        $content = $this->ui()->viewForm($formId, $materializedView);
        $buttons = [[
            'title' => 'Cancel',
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ],[
            'title' => 'Save',
            'class' => 'btn btn-primary',
            'click' => $this->rq()->create(pm()->form($formId)),
        ]];
        $this->modal()->show($title, $content, $buttons);
    }

    /**
     * Create a new view
     *
     * @param array $values      The view values
     *
     * @return void
     */
    public function create(array $values)
    {
        $values['materialized'] = \array_key_exists('materialized', $values);

        $result = $this->db()->createView($values);
        if(!$result['success'])
        {
            $this->alert()->error($result['error']);
            return;
        }

        $this->modal()->hide();
        $this->cl(Database::class)->showViews();
        $this->alert()->success($result['message']);
    }

    /**
     * Show edit form for a given view
     *
     * @param string $view        The view name
     *
     * @return void
     */
    public function edit(string $view)
    {
        $viewData = $this->db()->getView($view);
        // Make view info available to views
        $this->view()->shareValues($viewData);

        $formId = 'view-form';
        $title = 'Edit a view';
        $materializedView = $this->db()->support('materializedview');
        $content = $this->ui()->viewForm($formId, $materializedView, $viewData['view']);
        $buttons = [[
            'title' => 'Cancel',
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ],[
            'title' => 'Save',
            'class' => 'btn btn-primary',
            'click' => $this->rq()->update($view, pm()->form($formId)),
        ]];
        $this->modal()->show($title, $content, $buttons);
    }

    /**
     * Update a given view
     *
     * @param string $view        The view name
     * @param array $values      The view values
     *
     * @return void
     */
    public function update(string $view, array $values)
    {
        $values['materialized'] = \array_key_exists('materialized', $values);

        $result = $this->db()->updateView($view, $values);
        if(!$result['success'])
        {
            $this->alert()->error($result['error']);
            return;
        }

        $this->modal()->hide();
        $this->cl(Database::class)->showViews();
        $this->alert()->success($result['message']);
    }

    /**
     * Drop a given view
     *
     * @param string $view        The view name
     *
     * @return void
     */
    public function drop(string $view)
    {
        $result = $this->db()->dropView($view);
        if(!$result['success'])
        {
            $this->alert()->error($result['error']);
            return;
        }

        $this->cl(Database::class)->showViews();
        $this->alert()->success($result['message']);
    }
}
