<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Database;

use Lagdo\DbAdmin\App\Ajax\Page\PageActions;

class UserTypes extends Component
{
    /**
     * Show the user types of a given database
     *
     * @after('call' => 'showBreadcrumbs')
     * @after('call' => 'selectMenuItem', 'with' => ['.menu-action-type', 'adminer-database-menu'])
     *
     * @return void
     */
    public function showUserTypes()
    {
        // Set main menu buttons
        $this->cl(PageActions::class)->dbUserTypes();

        $this->showSection($this->db->getUserTypes());
    }
}
