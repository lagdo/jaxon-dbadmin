<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Server;

use Jaxon\Attributes\Attribute\After;
use Lagdo\DbAdmin\App\Ajax\Admin\Page\PageActions;

class Processes extends MainComponent
{
    /**
     * @inheritDoc
     */
    protected function before(): void
    {
        $this->activateServerSectionMenu('processes');

        // Set main menu buttons
        $this->cl(PageActions::class)->clear();
    }

    /**
     * @inheritDoc
     */
    protected function content(): string
    {
        return $this->ui()->pageContent($this->get('content'));
    }

    /**
     * Show the processes of a server
     *
     * @return void
     */
    #[After('showBreadcrumbs')]
    public function show(): void
    {
        $this->set('content', $this->db()->getProcesses());

        $this->render();
    }
}
