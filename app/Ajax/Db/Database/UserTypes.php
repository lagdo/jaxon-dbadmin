<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Database;

use Jaxon\Response\Response;

class UserTypes extends Component
{
    /**
     * Show the user types of a given database
     *
     * @after('call' => 'showBreadcrumbs')
     * @after('call' => 'selectMenuItem', 'with' => ['.menu-action-type', 'adminer-database-menu'])
     *
     * @return Response
     */
    public function showUserTypes(): Response
    {
        $userTypesInfo = $this->db->getUserTypes();
        $actions = [
            // [$this->trans->lang('Create type'), ],
        ];
        $this->showSection($userTypesInfo, [], $actions);

        return $this->response;
    }
}
