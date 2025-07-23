<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Database;

use Lagdo\DbAdmin\Ajax\App\Page\PageActions;

class Events extends ContentComponent
{
    /**
     * @inheritDoc
     */
    protected function before(): void
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
    public function show(): void
    {
        $this->showSection($this->db()->getEvents());
    }
}
