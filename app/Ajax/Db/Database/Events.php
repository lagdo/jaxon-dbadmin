<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Database;

use Jaxon\Response\Response;

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
        $eventsInfo = $this->db->getEvents();

        $actions = [
            // [$this->trans->lang('Create event'), ],
        ];
        $this->showSection($eventsInfo, [], $actions);

        return $this->response;
    }
}
