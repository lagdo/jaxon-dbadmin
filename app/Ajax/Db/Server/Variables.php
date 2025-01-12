<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Server;

use Lagdo\DbAdmin\App\Component;
use Lagdo\DbAdmin\App\Ajax\Page\PageActions;

class Variables extends Component
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
     * Show the variables of a server
     *
     * @after('call' => 'showBreadcrumbs')
     * @after('call' => 'selectMenuItem', 'with' => ['.menu-action-variables', 'adminer-database-menu'])
     *
     * @return void
     */
    public function update()
    {
        $this->pageContent = $this->db->getVariables();

        // Set main menu buttons
        $this->cl(PageActions::class)->clear();

        $this->render();
    }
}
