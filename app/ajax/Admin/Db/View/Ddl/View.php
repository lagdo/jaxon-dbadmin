<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\View\Ddl;

use Jaxon\Attributes\Attribute\After;
use Jaxon\Attributes\Attribute\Before;
use Lagdo\DbAdmin\Ajax\Admin\Db\Database\Views;
use Lagdo\DbAdmin\Ajax\Admin\Db\FuncComponent;
use Lagdo\DbAdmin\Ajax\Admin\Db\View\Dql\Select;
use Lagdo\DbAdmin\Ajax\Admin\Page\Content;
use Lagdo\DbAdmin\Ajax\Admin\Page\PageActions;
use Lagdo\DbAdmin\Db\Config\ServerConfig;
use Lagdo\DbAdmin\Db\Driver\DbFacade;
use Lagdo\DbAdmin\Db\Translator;
use Lagdo\DbAdmin\Ui\Table\ViewUiBuilder;

use function is_array;

class View extends FuncComponent
{
    /**
     * The constructor
     *
     * @param ServerConfig   $config     The package config reader
     * @param DbFacade       $db         The facade to database functions
     * @param ViewUiBuilder  $viewUi     The HTML UI builder
     * @param Translator     $trans
     */
    public function __construct(protected ServerConfig $config, protected DbFacade $db,
        protected ViewUiBuilder $viewUi, protected Translator $trans)
    {}

    /**
     * Display the content of a tab
     *
     * @param array  $viewData  The data to be displayed in the view
     * @param string $tabId     The tab container id
     *
     * @return void
     */
    protected function showTab(array $viewData, string $tabId): void
    {
        $this->response()->html($tabId, $this->viewUi->pageContent($viewData));
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
     * @param string $view        The view name
     *
     * @return void
     */
    #[After('showBreadcrumbs')]
    public function show(string $view): void
    {
        $viewInfo = $this->db()->getViewInfo($view);

        // Set main menu buttons
        // $actions = [
        //     $this->trans()->lang('Add trigger'),
        // ];

        // $actions = $this->getViewLinks();

        $actions = [
            'select-view' => [
                'title' => $this->trans()->lang('Select'),
                'handler' => $this->rq(Select::class)->show($view),
            ],
            'edit-view' => [
                'title' => $this->trans()->lang('Edit view'),
                'handler' => $this->rq(Form::class)->edit($view),
            ],
            'drop-view' => [
                'title' => $this->trans()->lang('Drop view'),
                'handler' => $this->rq()->drop($view)->confirm("Drop view $view?"),
            ],
            'back-views' => [
                'title' => $this->trans()->lang('Back'),
                'handler' => $this->rq(Views::class)->show(),
            ],
        ];
        $this->cl(PageActions::class)->show($actions);

        $content = $this->viewUi->mainDbTable($viewInfo['tabs']);
        $this->cl(Content::class)->showHtml($content);

        // Show fields
        $fieldsInfo = $this->db()->getViewFields($view);
        $this->showTab($fieldsInfo, $this->tabId('tab-content-fields'));

        // Show triggers
        $triggersInfo = $this->db()->getViewTriggers($view);
        if(is_array($triggersInfo))
        {
            $this->showTab($triggersInfo, $this->tabId('tab-content-triggers'));
        }
    }

    /**
     * Create a new view
     *
     * @param array $values      The view values
     *
     * @return void
     */
    #[Before('notYetAvailable')]
    public function create(array $values): void
    {
        $values['materialized'] = isset($values['materialized']);

        $result = $this->db()->createView($values);
        if(!$result['success'])
        {
            $this->alert()->error($result['error']);
            return;
        }

        $this->cl(Views::class)->show();
        $this->alert()->success($result['message']);
    }

    /**
     * Update a given view
     *
     * @param string $view        The view name
     * @param array $values      The view values
     *
     * @return void
     */
    #[Before('notYetAvailable')]
    public function update(string $view, array $values): void
    {
        $values['materialized'] = isset($values['materialized']);

        $result = $this->db()->updateView($view, $values);
        if(!$result['success'])
        {
            $this->alert()->error($result['error']);
            return;
        }

        $this->show($view);
        $this->alert()->success($result['message']);
    }

    /**
     * Drop a given view
     *
     * @param string $view        The view name
     *
     * @return void
     */
    #[Before('notYetAvailable')]
    public function drop(string $view): void
    {
        $result = $this->db()->dropView($view);
        if(!$result['success'])
        {
            $this->alert()->error($result['error']);
            return;
        }

        $this->cl(Views::class)->show();
        $this->alert()->success($result['message']);
    }
}
