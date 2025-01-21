<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Database;

use Lagdo\DbAdmin\App\Ajax\Page\PageActions;

class Sequences extends Component
{
    /**
     * @inheritDoc
     */
    protected function before()
    {
        $this->activateDatabaseSectionMenu('sequences');
        // Set main menu buttons
        $this->cl(PageActions::class)->dbSequences();
    }

    /**
     * Show the sequences of a given database
     *
     * @return void
     */
    public function refresh()
    {
        $this->showSection($this->db->getSequences());
    }
}
