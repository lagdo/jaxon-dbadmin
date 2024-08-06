<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Database;

use Jaxon\Response\Response;
use Lagdo\DbAdmin\App\Ajax\Page\PageActions;

class Routines extends Component
{
    /**
     * Show the routines of a given database
     *
     * @after('call' => 'showBreadcrumbs')
     * @after('call' => 'selectMenuItem', 'with' => ['.menu-action-routine', 'adminer-database-menu'])
     *
     * @return Response
     */
    public function update(): Response
    {
        // Set main menu buttons
        $this->cl(PageActions::class)->dbRoutines();

        $this->showSection($this->db->getRoutines());

        return $this->response;
    }
}
