<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Database;

use Lagdo\DbAdmin\App\Ajax\Admin\Db\View\Ddl\Form;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\View\Ddl\View;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\View\Ddl\ViewFunc;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\View\Dql\Select;
use Lagdo\DbAdmin\App\Ajax\Admin\Page\PageActions;

use function array_keys;
use function array_map;

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
        $details = $viewsInfo['details'];

        // Add links, classes and data values to view names. The $details['name']
        // value is wrapped into a <div>, the cannot be used as param for calls.
        $viewsInfo['details'] = array_map(fn(array $detail, string $view) => [
            ...$detail,
            'menu' => $this->ui()->buttonMenu([[
                'label' => $this->trans->lang('Select'),
                'handler' => $this->rq(Select::class)->show($view),
            ], [
                'label' => $this->trans->lang('Show'),
                'handler' => $this->rq(View::class)->show($view),
            ], [
                'label' => $this->trans->lang('Drop'),
                'handler' => $this->rq(ViewFunc::class)->drop($view)
                    ->confirm($this->trans->lang('Drop view %s?', $view)),
            ]]),
        ], $details, array_keys($details));

        $this->showSection($viewsInfo, 'view');

        // Set onclick handlers on view checkbox
        $this->response()->jo('jaxon.dbadmin')
            ->selectTableCheckboxes(...$this->ui()->contentIds('view'));
    }
}
