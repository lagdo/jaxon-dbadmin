<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Database;

use Jaxon\Response\Response;

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
        $sequencesInfo = $this->db->getSequences();

        $actions = [
            // [$this->trans->lang('Create sequence'), ],
        ];
        $this->showSection($sequencesInfo, [], $actions);

        return $this->response;
    }
}
