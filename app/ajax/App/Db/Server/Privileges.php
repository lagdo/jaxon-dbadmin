<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Server;

use Jaxon\Attributes\Attribute\After;
use Jaxon\Attributes\Attribute\Inject;
use Lagdo\DbAdmin\Ajax\App\Page\PageActions;
use Lagdo\DbAdmin\Ui\InputBuilder;

use function array_map;
use function Jaxon\jq;

class Privileges extends MainComponent
{
    /**
     * @var array
     */
    private $pageContent;

    /**
     * @var InputBuilder
     */
    #[Inject]
    private InputBuilder $inputUi;

    /**
     * @inheritDoc
     */
    protected function before(): void
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
        return $this->ui()->pageContent($this->pageContent);
    }

    /**
     * Show the privileges of a server
     *
     * @return void
     */
    #[After('showBreadcrumbs')]
    public function show(): void
    {
        $this->pageContent = $this->db()->getPrivileges();

        $user = jq()->parent()->attr('data-user');
        $host = jq()->parent()->attr('data-host');
        $database = jq()->parent()->parent()->find("option.database-item:selected")->val();
        // Add links, classes and data values to privileges.
        $this->pageContent['details'] = array_map(function($detail) use($user, $host, $database) {
            // Set the grant select options.
            $detail['grants'] = $this->inputUi->htmlSelect($detail['grants'], 'database-item');
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
