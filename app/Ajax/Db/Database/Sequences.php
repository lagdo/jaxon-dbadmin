<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Database;

use Jaxon\Response\Response;
use Lagdo\DbAdmin\App\Ajax\Page\PageActions;

class Sequences extends Component
{
    /**
     * Show the sequences of a given database
     *
     * @after('call' => 'showBreadcrumbs')
     * @after('call' => 'selectMenuItem', 'with' => ['.menu-action-sequence', 'adminer-database-menu'])
     *
     * @return Response
     */
    public function update(): Response
    {
        // Set main menu buttons
        $this->cl(PageActions::class)->dbSequences();

        $this->showSection($this->db->getSequences());

        return $this->response;
    }
}
