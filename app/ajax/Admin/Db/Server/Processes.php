<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Server;

use Jaxon\Attributes\Attribute\After;
use Lagdo\DbAdmin\Ajax\Admin\Page\PageActions;

class Processes extends MainComponent
{
    /**
     * @var array
     */
    private $pageContent;

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
    public function html(): string
    {
        return $this->ui()->pageContent($this->pageContent);
    }

    /**
     * Show the processes of a server
     *
     * @return void
     */
    #[After('showBreadcrumbs')]
    public function show(): void
    {
        $this->pageContent = $this->db()->getProcesses();

        $this->render();
    }
}
