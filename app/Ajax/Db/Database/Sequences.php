<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Database;

use Lagdo\DbAdmin\App\Ajax\Page\PageActions;

class Sequences extends Component
{
    /**
     * Show the sequences of a given database
     *
     * @after('call' => 'showBreadcrumbs')
     * @after('call' => 'selectMenuItem', 'with' => ['.menu-action-sequence', 'adminer-database-menu'])
     *
     * @return void
     */
    public function update()
    {
        // Set main menu buttons
        $this->cl(PageActions::class)->dbSequences();

        $this->showSection($this->db->getSequences());
    }
}
