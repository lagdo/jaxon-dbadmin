<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Server;

use Jaxon\Response\AjaxResponse;
use Lagdo\DbAdmin\App\Component;
use Lagdo\DbAdmin\App\Ajax\Page\PageActions;

class Processes extends Component
{
    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->ui->mainContent($this->renderMainContent());
    }

    /**
     * Show the processes of a server
     *
     * @after('call' => 'showBreadcrumbs')
     * @after('call' => 'selectMenuItem', 'with' => ['.menu-action-processes', 'adminer-database-menu'])
     *
     * @return AjaxResponse
     */
    public function update(): AjaxResponse
    {
        $processesInfo = $this->db->getProcesses();
        // Make processes info available to views
        $this->view()->shareValues($processesInfo);

        // Set main menu buttons
        $this->cl(PageActions::class)->clear();

        return $this->render();
    }
}
