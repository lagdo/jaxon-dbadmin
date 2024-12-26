<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Database;

use Jaxon\Response\Response;
use Lagdo\DbAdmin\App\Ajax\Page\PageActions;

class Sequences extends Component
{
    /**
     * @inheritDoc
     */
    protected function before()
    {
        $this->activateDatabaseSectionMenu('sequences');
        // Set main menu buttons
        $this->cl(PageActions::class)->dbSequences();
    }

    /**
     * Show the sequences of a given database
     *
     * @return Response
     */
    public function refresh(): Response
    {
        $this->showSection($this->db->getSequences());

        return $this->response;
    }
}
