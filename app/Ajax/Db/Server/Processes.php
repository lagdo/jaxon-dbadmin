<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Server;

use Jaxon\Response\Response;
use Lagdo\DbAdmin\App\Ajax\Menu\Actions as MenuActions;
use Lagdo\DbAdmin\App\Ajax\Page\PageActions;

class Processes extends Component
{
    /**
     * @var array
     */
    private $pageContent;

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->ui->mainContent($this->pageContent);
    }

    /**
     * Show the processes of a server
     *
     * @return Response
     */
    public function refresh(): Response
    {
        $this->pageContent = $this->db->getProcesses();

        // Side menu actions
        $this->cl(MenuActions::class)->server('processes');
        // Set main menu buttons
        $this->cl(PageActions::class)->clear();

        return $this->render();
    }
}
