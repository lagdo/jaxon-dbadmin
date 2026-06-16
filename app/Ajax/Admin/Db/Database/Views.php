<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Database;

use Lagdo\DbAdmin\App\Ajax\Admin\Db\Table\CodeFunc;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\View\Ddl\Form;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\View\Ddl\View;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\View\Ddl\ViewFunc;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\View\Dql\Select;
use Lagdo\DbAdmin\App\Ajax\Admin\Page\PageActions;

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

        foreach($viewsInfo['details'] as $name => $detail) {
            $detail->menus = [[
                'label' => $this->trans->lang('Select'),
                'handler' => $this->rq(Select::class)->show($name),
            ], [
                'label' => $this->trans->lang('Show'),
                'handler' => $this->rq(View::class)->show($name),
            ], [
                'label' => $this->trans->lang('Drop query'),
                'handler' => $this->rq(CodeFunc::class)->showDropViewQuery($name),
            ], [
                'label' => $this->trans->lang('Drop'),
                'handler' => $this->rq(ViewFunc::class)->drop($name)
                    ->confirm($this->trans->lang('Drop view %s?', $name)),
            ]];
        }

        $this->showSection($viewsInfo, 'view');

        // Set onclick handlers on view checkbox
        $this->response()->jo('jaxon.dbadmin')
            ->selectTableCheckboxes(...$this->ui()->contentIds('view'));
    }
}
