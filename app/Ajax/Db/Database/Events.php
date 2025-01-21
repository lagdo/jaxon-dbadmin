<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Database;

use Lagdo\DbAdmin\App\Ajax\Page\PageActions;

class Events extends Component
{
    /**
     * @inheritDoc
     */
    protected function before()
    {
        $this->activateDatabaseSectionMenu('events');
        // Set main menu buttons
        $this->cl(PageActions::class)->dbEvents();
    }

    /**
     * Show the events of a given database
     *
     * @return void
     */
    public function refresh()
    {
        $this->showSection($this->db->getEvents());
    }
}
