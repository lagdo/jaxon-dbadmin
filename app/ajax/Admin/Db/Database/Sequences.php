<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Database;

use Lagdo\DbAdmin\Ajax\Admin\Page\PageActions;

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
