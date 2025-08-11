<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Database;

use Lagdo\DbAdmin\Ajax\App\Page\PageActions;

class Routines extends MainComponent
{
    /**
     * @inheritDoc
     */
    protected function before(): void
    {
        $this->activateDatabaseSectionMenu('routines');
        // Set main menu buttons
        $this->cl(PageActions::class)->show([
            // 'add-procedure' => [
            //     'title' => $this->trans()->lang('Create procedure'),
            // ],
            // 'add-function' => [
            //     'title' => $this->trans()->lang('Create function'),
            // ],
        ]);
    }

    /**
     * Show the routines of a given database
     *
     * @return void
     */
    public function show(): void
    {
        $this->showSection($this->db()->getRoutines());
    }
}
