<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Database;

use Jaxon\Response\Response;
use Lagdo\DbAdmin\App\Ajax\Menu\Actions as MenuActions;
use Lagdo\DbAdmin\App\Ajax\Page\PageActions;

class Sequences extends Component
{
    /**
     * Show the sequences of a given database
     *
     * @return Response
     */
    public function refresh(): Response
    {
        // Side menu actions
        $this->cl(MenuActions::class)->database('sequences');
        // Set main menu buttons
        $this->cl(PageActions::class)->dbSequences();

        $this->showSection($this->db->getSequences());

        return $this->response;
    }
}
