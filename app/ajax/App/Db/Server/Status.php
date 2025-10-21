<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Server;

use Jaxon\Attributes\Attribute\After;
use Lagdo\DbAdmin\Ajax\App\Page\PageActions;

class Status extends MainComponent
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
        $this->activateServerSectionMenu('status');
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
     * Show the status of a server
     *
     * @return void
     */
    #[After('showBreadcrumbs')]
    public function show(): void
    {
        $this->pageContent = $this->db()->getStatus();

        $this->render();
    }
}
