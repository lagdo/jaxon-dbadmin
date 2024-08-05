<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Database;

use Jaxon\Response\Response;

class Routines extends Component
{
    /**
     * Show the routines of a given database
     *
     * @after('call' => 'showBreadcrumbs')
     * @after('call' => 'selectMenuItem', 'with' => ['.menu-action-routine', 'adminer-database-menu'])
     *
     * @return Response
     */
    public function update(): Response
    {
        $routinesInfo = $this->db->getRoutines();

        $actions = [
            // [$this->trans->lang('Create procedure'), ],
            // [$this->trans->lang('Create function'), ],
        ];
        $this->showSection($routinesInfo, [], $actions);

        return $this->response;
    }
}
