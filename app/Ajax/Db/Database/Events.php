<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Database;

use Lagdo\DbAdmin\App\Ajax\Page\PageActions;

class Events extends Component
{
    /**
     * Show the events of a given database
     *
     * @after('call' => 'showBreadcrumbs')
     * @after('call' => 'selectMenuItem', 'with' => ['.menu-action-event', 'adminer-database-menu'])
     *
     * @return void
     */
    public function update()
    {
        // Set main menu buttons
        $this->cl(PageActions::class)->dbEvents();

        $this->showSection($this->db->getEvents());
    }
}
