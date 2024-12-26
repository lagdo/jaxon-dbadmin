<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Database;

use Jaxon\Response\Response;
use Lagdo\DbAdmin\App\Ajax\Page\PageActions;

class Routines extends Component
{
    /**
     * @inheritDoc
     */
    protected function before()
    {
        $this->activateDatabaseSectionMenu('routines');
        // Set main menu buttons
        $this->cl(PageActions::class)->dbRoutines();
    }

    /**
     * Show the routines of a given database
     *
     * @return Response
     */
    public function refresh(): Response
    {
        $this->showSection($this->db->getRoutines());

        return $this->response;
    }
}
