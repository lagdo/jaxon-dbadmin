<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Server;

use Lagdo\DbAdmin\Ajax\App\Db\FuncComponent;

use function Jaxon\pm;

class Privilege extends FuncComponent
{
    /**
     * Show the new user form
     *
     * @return void
     */
    public function add(): void
    {
        $userInfo = $this->db()->newUserPrivileges();

        $formId = 'user-form';
        $title = 'Add user privileges';
        $privileges = $this->ui()->mainContent($userInfo);
        $content = $this->ui()->userForm($formId, $userInfo['user'], $privileges);

        $buttons = [[
            'title' => 'Cancel',
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ],[
            'title' => 'Save',
            'class' => 'btn btn-primary',
            'click' => $this->rq()->create(pm()->form($formId)),
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

        $formId = 'user-form';
        $title = 'Edit user privileges';
        $privileges = $this->ui()->mainContent($userInfo);
        $content = $this->ui()->userForm($formId, $userInfo['user'], $privileges);

        $buttons = [[
            'title' => 'Cancel',
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ],[
            'title' => 'Save',
            'class' => 'btn btn-primary',
            'click' => $this->rq()->update(pm()->form($formId)),
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
