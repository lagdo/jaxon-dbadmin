<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Database;

use Jaxon\Response\Response;

use function Jaxon\jq;

class Views extends Component
{
    /**
     * Show the views of a given database
     *
     * @after('call' => 'showBreadcrumbs')
     * @after('call' => 'selectMenuItem', 'with' => ['.menu-action-view', 'adminer-database-menu'])
     *
     * @return Response
     */
    public function update(): Response
    {
        $viewsInfo = $this->db->getViews();

        $viewNameClass = 'adminer-view-name';
        // Add links, classes and data values to view names.
        $viewsInfo['details'] = \array_map(function($detail) use($viewNameClass) {
            $detail['name'] = [
                'label' => '<a href="javascript:void(0)">' . $detail['name'] . '</a>',
                'props' => [
                    'class' => $viewNameClass,
                    'data-name' => $detail['name'],
                ],
            ];
            return $detail;
        }, $viewsInfo['details']);

        $actions = [
            [$this->trans->lang('Create view'), $this->rq(View::class)->add()],
        ];
        $checkbox = 'view';
        $this->showSection($viewsInfo, ['checkbox' => $checkbox], $actions);

        // Set onclick handlers on view checkbox
        $this->response->call("jaxon.dbadmin.selectTableCheckboxes", $checkbox);
        // Set onclick handlers on view names
        $view = jq()->parent()->attr('data-name');
        $this->jq('.' . $viewNameClass . '>a', '#' . $this->package->getDbContentId())
            ->click($this->rq(View::class)->show($view));

        return $this->response;
    }
}
