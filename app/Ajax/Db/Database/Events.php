<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Database;

use Lagdo\DbAdmin\App\Ajax\Page\PageActions;

class Events extends ContentComponent
{
    /**
     * @inheritDoc
     */
    protected function before()
    {
        $this->activateDatabaseSectionMenu('events');
        // Set main menu buttons
        $this->cl(PageActions::class)->show([
            // 'add-event' => [
            //     'title' => $this->trans()->lang('Create event'),
            // ],
        ]);
    }

    /**
     * Show the events of a given database
     *
     * @return void
     */
    public function show()
    {
        $this->showSection($this->db()->getEvents());
    }
}
