<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Server;

use Lagdo\DbAdmin\App\Ajax\Page\PageActions;

class Processes extends ContentComponent
{
    /**
     * @var array
     */
    private $pageContent;

    /**
     * @inheritDoc
     */
    protected function before()
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
        return $this->ui()->mainContent($this->pageContent);
    }

    /**
     * Show the processes of a server
     *
     * @return void
     */
    public function show()
    {
        $this->pageContent = $this->db()->getProcesses();

        $this->render();
    }
}
