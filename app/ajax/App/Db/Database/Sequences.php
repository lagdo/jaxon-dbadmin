<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Database;

use Lagdo\DbAdmin\Ajax\App\Page\PageActions;

class Sequences extends MainComponent
{
    /**
     * @inheritDoc
     */
    protected function before(): void
    {
        $this->activateDatabaseSectionMenu('sequences');
        // Set main menu buttons
        $this->cl(PageActions::class)->show([
            // 'add-sequence' => [
            //     'title' => $this->trans()->lang('Create sequence'),
            // ],
        ]);
    }

    /**
     * Show the sequences of a given database
     *
     * @return void
     */
    public function show(): void
    {
        $this->showSection($this->db()->getSequences());
    }
}
