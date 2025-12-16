<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Database;

use Lagdo\DbAdmin\Ajax\Admin\Page\PageActions;

class UserTypes extends MainComponent
{
    /**
     * @inheritDoc
     */
    protected function before(): void
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
    public function show(): void
    {
        $this->showSection($this->db()->getUserTypes());
    }
}
