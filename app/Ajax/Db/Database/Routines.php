<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Database;

use Lagdo\DbAdmin\App\Ajax\Page\PageActions;

class Routines extends Component
{
    /**
     * @inheritDoc
     */
    protected function before()
    {
        $this->activateDatabaseSectionMenu('routines');
        // Set main menu buttons
        $this->cl(PageActions::class)->dbRoutines();
    }

    /**
     * Show the routines of a given database
     *
     * @return void
     */
    public function refresh()
    {
        $this->showSection($this->db->getRoutines());
    }
}
