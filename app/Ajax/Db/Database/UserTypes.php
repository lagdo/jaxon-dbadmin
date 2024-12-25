<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Database;

use Jaxon\Response\Response;
use Lagdo\DbAdmin\App\Ajax\Menu\Actions as MenuActions;
use Lagdo\DbAdmin\App\Ajax\Page\PageActions;

class UserTypes extends Component
{
    /**
     * Show the user types of a given database
     *
     * @return Response
     */
    public function showUserTypes(): Response
    {
        // Side menu actions
        $this->cl(MenuActions::class)->database('types');
        // Set main menu buttons
        $this->cl(PageActions::class)->dbUserTypes();

        $this->showSection($this->db->getUserTypes());

        return $this->response;
    }
}
