<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Database;

use Jaxon\Response\Response;
use Lagdo\DbAdmin\App\Ajax\Menu\Actions as MenuActions;
use Lagdo\DbAdmin\App\Ajax\Page\PageActions;

class Events extends Component
{
    /**
     * Show the events of a given database
     *
     * @return Response
     */
    public function refresh(): Response
    {
        // Side menu actions
        $this->cl(MenuActions::class)->database('events');
        // Set main menu buttons
        $this->cl(PageActions::class)->dbEvents();

        $this->showSection($this->db->getEvents());

        return $this->response;
    }
}
