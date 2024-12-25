<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Database;

use Jaxon\Response\Response;
use Lagdo\DbAdmin\App\Ajax\Menu\Actions as MenuActions;
use Lagdo\DbAdmin\App\Ajax\Page\PageActions;

class Routines extends Component
{
    /**
     * Show the routines of a given database
     *
     * @return Response
     */
    public function refresh(): Response
    {
        // Side menu actions
        $this->cl(MenuActions::class)->database('routines');
        // Set main menu buttons
        $this->cl(PageActions::class)->dbRoutines();

        $this->showSection($this->db->getRoutines());

        return $this->response;
    }
}
