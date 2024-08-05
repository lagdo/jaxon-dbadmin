<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Server;

use Jaxon\Response\AjaxResponse;
use Lagdo\DbAdmin\App\Component;
use Lagdo\DbAdmin\App\Ajax\Page\PageActions;

class Variables extends Component
{
    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->ui->mainContent($this->renderMainContent());
    }

    /**
     * Show the variables of a server
     *
     * @after('call' => 'showBreadcrumbs')
     * @after('call' => 'selectMenuItem', 'with' => ['.menu-action-variables', 'adminer-database-menu'])
     *
     * @return AjaxResponse
     */
    public function update(): AjaxResponse
    {
        $variablesInfo = $this->db->getVariables();
        // Make variables info available to views
        $this->view()->shareValues($variablesInfo);

        // Set main menu buttons
        $this->cl(PageActions::class)->clear();

        return $this->render();
    }
}
