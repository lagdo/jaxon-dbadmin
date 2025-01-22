<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Database;

use Lagdo\DbAdmin\App\Ajax\Page\PageActions;

class Sequences extends ContentComponent
{
    /**
     * @inheritDoc
     */
    protected function before()
    {
        $this->activateDatabaseSectionMenu('sequences');
        // Set main menu buttons
        $this->cl(PageActions::class)->show([
            // 'add-sequence' => [
            //     'title' => $this->trans->lang('Create sequence'),
            // ],
        ]);
    }

    /**
     * Show the sequences of a given database
     *
     * @return void
     */
    public function show()
    {
        $this->showSection($this->db->getSequences());
    }
}
