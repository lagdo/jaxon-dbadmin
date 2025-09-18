<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\View\Ddl;

use Lagdo\DbAdmin\Ajax\App\Db\Database\Views;
use Lagdo\DbAdmin\Ajax\App\Db\FuncComponent;
use Lagdo\DbAdmin\Ajax\App\Db\View\Dql\Select;
use Lagdo\DbAdmin\Ajax\App\Page\Content;
use Lagdo\DbAdmin\Ajax\App\Page\PageActions;
use Lagdo\DbAdmin\DbAdminPackage;
use Lagdo\DbAdmin\Db\DbFacade;
use Lagdo\DbAdmin\Translator;
use Lagdo\DbAdmin\Ui\Table\ViewUiBuilder;

use function is_array;

class View extends FuncComponent
{
    /**
     * The constructor
     *
     * @param DbAdminPackage $package    The DbAdmin package
     * @param DbFacade       $db         The facade to database functions
     * @param ViewUiBuilder  $viewUi     The HTML UI builder
     * @param Translator     $trans
     */
    public function __construct(protected DbAdminPackage $package, protected DbFacade $db,
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
        $this->response->html($tabId, $this->viewUi->pageContent($viewData));
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
        $this->showTab($fieldsInfo, 'tab-content-fields');

        // Show triggers
        $triggersInfo = $this->db()->getViewTriggers($view);
        if(is_array($triggersInfo))
        {
            $this->showTab($triggersInfo, 'tab-content-triggers');
        }
    }

    /**
     * Create a new view
     * @before notYetAvailable
     *
     * @param array $values      The view values
     *
     * @return void
     */
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
     * @before notYetAvailable
     *
     * @param string $view        The view name
     * @param array $values      The view values
     *
     * @return void
     */
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
     * @before notYetAvailable
     *
     * @param string $view        The view name
     *
     * @return void
     */
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
