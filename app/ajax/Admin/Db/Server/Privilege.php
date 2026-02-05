<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Server;

use Jaxon\Attributes\Attribute\Before;
use Lagdo\DbAdmin\Ajax\Admin\Db\FuncComponent;
use Lagdo\DbAdmin\Ui\Database\ServerUiBuilder;

use function Jaxon\form;

#[Before('notYetAvailable')]
class Privilege extends FuncComponent
{
    /**
     * The constructor
     *
     * @param ServerUiBuilder $serverUi The HTML UI builder
     */
    public function __construct(protected ServerUiBuilder $serverUi)
    {}

    /**
     * Show the new user form
     *
     * @return void
     */
    public function add(): void
    {
        $userInfo = $this->db()->newUserPrivileges();

        $title = 'Add user privileges';
        $privileges = $this->serverUi->pageContent($userInfo);
        $content = $this->serverUi->addUserForm($userInfo['user'], $privileges);

        $buttons = [[
            'title' => 'Cancel',
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ],[
            'title' => 'Save',
            'class' => 'btn btn-primary',
            'click' => $this->rq()->create(form($this->serverUi->userFormId())),
        ]];
        $this->modal()->show($title, $content, $buttons);
    }

    /**
     * Save new user privileges
     *
     * @param array  $formValues  The form values
     *
     * @return void
     */
    public function create(array $formValues): void
    {
        $this->modal()->hide();
        $this->alert()->warning("This feature is not yet implemented.");
        // $this->alert()->info("User privileges created.");
    }

    /**
     * Show the edit user form
     *
     * @param string $username  The user name
     * @param string $hostname  The host name
     * @param string $database  The database name
     *
     * @return void
     */
    public function edit(string $username, string $hostname, string $database): void
    {
        $userInfo = $this->db()->getUserPrivileges($username, $hostname, $database);

        $title = 'Edit user privileges';
        $privileges = $this->serverUi->pageContent($userInfo);
        $content = $this->serverUi->addUserForm($userInfo['user'], $privileges);

        $buttons = [[
            'title' => 'Cancel',
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ],[
            'title' => 'Save',
            'class' => 'btn btn-primary',
            'click' => $this->rq()->update(form($this->serverUi->userFormId())),
        ]];
        $this->modal()->show($title, $content, $buttons);
    }

    /**
     * Update user privileges
     *
     * @param array  $formValues  The form values
     *
     * @return void
     */
    public function update(array $formValues): void
    {
        $this->modal()->hide();
        $this->alert()->warning("This feature is not yet implemented.");
        // $this->alert()->info("User privileges updated.");
    }
}
