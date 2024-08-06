<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Database;

use Jaxon\Response\Response;
use Lagdo\DbAdmin\App\Ajax\Page\PageActions;

class Events extends Component
{
    /**
     * Show the events of a given database
     *
     * @after('call' => 'showBreadcrumbs')
     * @after('call' => 'selectMenuItem', 'with' => ['.menu-action-event', 'adminer-database-menu'])
     *
     * @return Response
     */
    public function update(): Response
    {
        // Set main menu buttons
        $this->cl(PageActions::class)->dbEvents();

        $this->showSection($this->db->getEvents());

        return $this->response;
    }
}
