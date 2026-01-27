<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Database;

use Lagdo\DbAdmin\Ajax\Admin\Db\View\Ddl\Form;
use Lagdo\DbAdmin\Ajax\Admin\Db\View\Ddl\View;
use Lagdo\DbAdmin\Ajax\Admin\Db\View\Ddl\ViewFunc;
use Lagdo\DbAdmin\Ajax\Admin\Db\View\Dql\Select;
use Lagdo\DbAdmin\Ajax\Admin\Page\PageActions;

class Views extends MainComponent
{
    /**
     * @inheritDoc
     */
    protected function before(): void
    {
        $this->activateDatabaseSectionMenu('views');
        // Set main menu buttons
        $this->cl(PageActions::class)->show([
            'add-view' => [
                'title' => $this->trans()->lang('Create view'),
                'handler' => $this->rq(Form::class)->add(),
            ],
        ]);
    }

    /**
     * Show the views of a given database
     *
     * @return void
     */
    public function show(): void
    {
        $viewsInfo = $this->db()->getViews();

        // Add links, classes and data values to view names.
        foreach($viewsInfo['details'] as &$detail) {
            $viewName = $detail['name'];
            $detail['menu'] = $this->ui()->tableMenu([[
                'label' => $this->trans->lang('Show'),
                'handler' => $this->rq(View::class)->show($viewName),
            ], [
                'label' => $this->trans->lang('Select'),
                'handler' => $this->rq(Select::class)->show($viewName),
            ], [
                'label' => $this->trans->lang('Drop'),
                'handler' => $this->rq(ViewFunc::class)->drop($viewName)
                    ->confirm($this->trans->lang('Drop view %s?', $viewName)),
            ]]);
        }

        $this->showSection($viewsInfo, 'view');

        // Set onclick handlers on view checkbox
        $this->response()->jo('jaxon.dbadmin')
            ->selectTableCheckboxes(...$this->ui()->contentIds('view'));
    }
}
