<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Server;

use Jaxon\Response\AjaxResponse;
use Lagdo\DbAdmin\App\Component;
use Lagdo\DbAdmin\App\Ajax\Db\User;
use Lagdo\DbAdmin\App\Ajax\Page\PageActions;

use function Jaxon\jq;

class Privileges extends Component
{
    /**
     * @var array
     */
    private $pageContent;

    /**
     * @inheritDoc
     */
    public function html(): string
    {
        return $this->ui->mainContent($this->pageContent);
    }

    /**
     * Show the privileges of a server
     *
     * @after('call' => 'showBreadcrumbs')
     * @after('call' => 'selectMenuItem', 'with' => ['.menu-action-privileges', 'adminer-database-menu'])
     *
     * @return AjaxResponse
     */
    public function update(): AjaxResponse
    {
        // Set main menu buttons
        $this->cl(PageActions::class)->userPrivileges();

        $this->pageContent = $this->db->getPrivileges();

        $editClass = 'adminer-privilege-name';
        $optionClass = 'jaxon-adminer-grant';
        // Add links, classes and data values to privileges.
        $this->pageContent['details'] = \array_map(function($detail) use($editClass, $optionClass) {
            // Set the grant select options.
            $detail['grants'] = $this->ui->htmlSelect($detail['grants'], $optionClass);
                // Set the Edit button.
            $detail['edit'] = [
                'label' => '<a href="javascript:void(0)">Edit</a>',
                'props' => [
                    'class' => $editClass,
                    'data-user' => $detail['user'],
                    'data-host' => $detail['host'],
                ],
            ];
            return $detail;
        }, $this->pageContent['details']);

        $this->render();

        // Set onclick handlers on database names
        $user = jq()->parent()->attr('data-user');
        $host = jq()->parent()->attr('data-host');
        $database = jq()->parent()->parent()->find("option.$optionClass:selected")->val();
        $this->jq('.' . $editClass . '>a', '#' . $this->package->getDbContentId())
            ->click($this->rq(User::class)->edit($user, $host, $database));

        return $this->response;
    }
}
