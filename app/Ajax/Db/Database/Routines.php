<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Database;

use Lagdo\DbAdmin\App\Ajax\Page\PageActions;

class Routines extends ContentComponent
{
    /**
     * @inheritDoc
     */
    protected function before()
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
    public function show()
    {
        $this->showSection($this->db()->getRoutines());
    }
}
