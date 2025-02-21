<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Server;

use Lagdo\DbAdmin\App\Ajax\Page\PageActions;

class Status extends ContentComponent
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
        $this->activateServerSectionMenu('status');
        // Set main menu buttons
        $this->cl(PageActions::class)->clear();
    }

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->ui->mainContent($this->pageContent);
    }

    /**
     * Show the status of a server
     *
     * @return void
     */
    public function show()
    {
        $this->pageContent = $this->db->getStatus();

        $this->render();
    }
}
