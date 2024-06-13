<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Server;

use Jaxon\Response\AjaxResponse;
use Lagdo\DbAdmin\App\Component;
use Lagdo\DbAdmin\App\Ajax\Page\PageActions;

class Status extends Component
{
    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->ui->mainContent($this->renderMainContent());
    }

    /**
     * Show the status of a server
     *
     * @after('call' => 'showBreadcrumbs')
     * @after('call' => 'selectMenuItem', 'with' => ['.menu-action-status', 'adminer-database-menu'])
     *
     * @return AjaxResponse
     */
    public function update(): AjaxResponse
    {
        $statusInfo = $this->db->getStatus();
        // Make status info available to views
        $this->view()->shareValues($statusInfo);

        // Set main menu buttons
        $this->cl(PageActions::class)->update([]);

        return $this->refresh();
    }
}
