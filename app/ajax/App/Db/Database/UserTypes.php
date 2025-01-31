<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Database;

use Lagdo\DbAdmin\Ajax\App\Page\PageActions;

class UserTypes extends ContentComponent
{
    /**
     * @inheritDoc
     */
    protected function before()
    {
        $this->activateDatabaseSectionMenu('types');
        // Set main menu buttons
        $this->cl(PageActions::class)->show([
            // 'add-type' => [
            //     'title' => $this->trans()->lang('Create type'),
            // ],
        ]);
    }

    /**
     * Show the user types of a given database
     *
     * @return void
     */
    public function showUserTypes()
    {
        $this->showSection($this->db()->getUserTypes());
    }
}
