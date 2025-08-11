<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Database;

use Lagdo\DbAdmin\Ajax\App\Db\View\View;
use Lagdo\DbAdmin\Ajax\App\Page\PageActions;

use function array_map;
use function Jaxon\jq;

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
                'handler' => $this->rq(View::class)->add(),
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

        $view = jq()->parent()->attr('data-view-name');
        // Add links, classes and data values to view names.
        $viewsInfo['details'] = array_map(function($detail) use($view) {
            $detail['show'] = [
                'label' => $detail['name'],
                'props' => [
                    'data-view-name' => $detail['name'],
                ],
                'handler' => $this->rq(View::class)->show($view),
            ];
            return $detail;
        }, $viewsInfo['details']);

        $checkbox = 'view';
        $this->showSection($viewsInfo, $checkbox);

        // Set onclick handlers on view checkbox
        $this->response->jo('jaxon.dbadmin')->selectTableCheckboxes($checkbox);
    }
}
