<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Database;

use Jaxon\Response\Response;
use Lagdo\DbAdmin\App\Ajax\Page\PageActions;

class UserTypes extends Component
{
    /**
     * @inheritDoc
     */
    protected function before()
    {
        $this->activateDatabaseSectionMenu('types');
        // Set main menu buttons
        $this->cl(PageActions::class)->dbUserTypes();
    }

    /**
     * Show the user types of a given database
     *
     * @return Response
     */
    public function showUserTypes(): Response
    {
        $this->showSection($this->db->getUserTypes());

        return $this->response;
    }
}
