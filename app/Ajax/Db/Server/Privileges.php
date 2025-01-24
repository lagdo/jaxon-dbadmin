<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Server;

use Lagdo\DbAdmin\App\Ajax\Page\PageActions;

use function array_map;
use function Jaxon\jq;

class Privileges extends ContentComponent
{
    /**
     * @var array
     */
    private $pageContent;

    /**
     * @inheritDoc
     */
    protected function before()
    {
        $this->activateServerSectionMenu('privileges');
        // Set main menu buttons
        $this->cl(PageActions::class)->show([
            'add-user' => [
                'title' => $this->trans()->lang('Create user'),
                'handler' => $this->rq(Privilege::class)->add(),
            ],
        ]);
    }

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->ui()->mainContent($this->pageContent);
    }

    /**
     * Show the privileges of a server
     *
     * @return void
     */
    public function show()
    {
        $this->pageContent = $this->db()->getPrivileges();

        $user = jq()->parent()->attr('data-user');
        $host = jq()->parent()->attr('data-host');
        $database = jq()->parent()->parent()->find("option.database-item:selected")->val();
        // Add links, classes and data values to privileges.
        $this->pageContent['details'] = array_map(function($detail) use($user, $host, $database) {
            // Set the grant select options.
            $detail['grants'] = $this->ui()->htmlSelect($detail['grants'], 'database-item');
            // Set the Edit button.
            $detail['edit'] = [
                'label' => 'Edit',
                'props' => [
                    'data-user' => $detail['user'],
                    'data-host' => $detail['host'],
                ],
                'handler' => $this->rq(Privilege::class)->edit($user, $host, $database),
            ];
            return $detail;
        }, $this->pageContent['details']);

        $this->render();
    }
}
