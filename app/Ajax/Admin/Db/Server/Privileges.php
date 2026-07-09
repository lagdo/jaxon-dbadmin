<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Server;

use Jaxon\Attributes\Attribute\After;
use Jaxon\Attributes\Attribute\Inject;
use Lagdo\DbAdmin\App\Ajax\Admin\Page\PageActions;
use Lagdo\DbAdmin\App\Ui\InputBuilder;

use function array_map;
use function Jaxon\jq;

class Privileges extends MainComponent
{
    /**
     * @var InputBuilder
     */
    #[Inject]
    protected InputBuilder $inputUi;

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
    protected function content(): string
    {
        return $this->ui()->pageContent($this->get('content'));
    }

    /**
     * Show the privileges of a server
     *
     * @return void
     */
    #[After('showBreadcrumbs')]
    public function show(): void
    {
        $pageContent = $this->driver()->getPrivileges();

        // Add links, classes and data values to privileges.
        $database = jq()->parent()->parent()->find("option.database-item:selected")->val();

        foreach($pageContent['details'] as $detail) {
            $detail->items['grants'] = $this->inputUi
                ->htmlSelect($detail->items['grants'], 'database-item');

            $user = $detail->items['user'];
            $host = $detail->items['host'];
            $detail->menus = [[
                'label' => $this->trans->lang('Edit'),
                'handler' => $this->rq(Privilege::class)->edit($user, $host, $database),
            ]];
        }

        $this->set('content', $pageContent);
        $this->render();
    }
}
