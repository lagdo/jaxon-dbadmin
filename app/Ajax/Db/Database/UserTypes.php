<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Database;

use Jaxon\Response\Response;
use Lagdo\DbAdmin\App\Ajax\Page\PageActions;

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
        // Set main menu buttons
        $this->cl(PageActions::class)->dbUserTypes();

        $this->showSection($this->db->getUserTypes());

        return $this->response;
    }
}
