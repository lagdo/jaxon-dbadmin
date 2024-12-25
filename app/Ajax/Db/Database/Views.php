<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Database;

use Jaxon\Response\Response;
use Lagdo\DbAdmin\App\Ajax\Db\View;
use Lagdo\DbAdmin\App\Ajax\Menu\Actions as MenuActions;
use Lagdo\DbAdmin\App\Ajax\Page\PageActions;

use function Jaxon\jq;

class Views extends Component
{
    /**
     * Show the views of a given database
     *
     * @return Response
     */
    public function refresh(): Response
    {
        // Side menu actions
        $this->cl(MenuActions::class)->database('views');
        // Set main menu buttons
        $this->cl(PageActions::class)->dbViews();

        $viewsInfo = $this->db->getViews();

        $view = jq()->parent()->attr('data-view-name');
        // Add links, classes and data values to view names.
        $viewsInfo['details'] = \array_map(function($detail) use($view) {
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
        $this->response->js('jaxon.dbadmin')->selectTableCheckboxes($checkbox);

        return $this->response;
    }
}
