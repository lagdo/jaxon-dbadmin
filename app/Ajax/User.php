<?php

namespace Lagdo\DbAdmin\App\Ajax;

use Jaxon\Response\Response;
use Lagdo\DbAdmin\App\CallableClass;

use Exception;

use function Jaxon\pm;

/**
 * @databag selection
 */
class User extends CallableClass
{
    /**
     * Show the new user form
     *
     * @return Response
     */
    public function add(): Response
    {
        [$server,] = $this->bag('selection')->get('db');
        $userInfo = $this->dbAdmin->newUserPrivileges($server);

        // Make user info available to views
        $this->view()->shareValues($userInfo);

        $formId = 'user-form';
        $title = 'Add user privileges';
        $privileges = $this->uiBuilder->mainContent($this->renderMainContent());
        $content = $this->uiBuilder->userForm($formId, $userInfo['user'], $privileges);

        $buttons = [[
            'title' => 'Cancel',
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ],[
            'title' => 'Save',
            'class' => 'btn btn-primary',
            'click' => $this->rq()->create(pm()->form($formId)),
        ]];
        $this->response->dialog->show($title, $content, $buttons);

        return $this->response;
    }

    /**
     * Save new user privileges
     *
     * @param array  $formValues  The form values
     *
     * @return Response
     */
    public function create(array $formValues): Response
    {
        $this->response->dialog->hide();
        $this->response->dialog->warning("This feature is not yet implemented.");
        // $this->response->dialog->info("User privileges created.");

        return $this->response;
    }

    /**
     * Show the edit user form
     *
     * @param string $username  The user name
     * @param string $hostname  The host name
     * @param string $database  The database name
     *
     * @return Response
     */
    public function edit(string $username, string $hostname, string $database): Response
    {
        [$server,] = $this->bag('selection')->get('db');
        $userInfo = $this->dbAdmin->getUserPrivileges($server, $username, $hostname, $database);

        // Make user info available to views
        $this->view()->shareValues($userInfo);

        $formId = 'user-form';
        $title = 'Edit user privileges';
        $privileges = $this->uiBuilder->mainContent($this->renderMainContent());
        $content = $this->uiBuilder->userForm($formId, $userInfo['user'], $privileges);

        $buttons = [[
            'title' => 'Cancel',
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ],[
            'title' => 'Save',
            'class' => 'btn btn-primary',
            'click' => $this->rq()->update(pm()->form($formId)),
        ]];
        $this->response->dialog->show($title, $content, $buttons);

        return $this->response;
    }

    /**
     * Update user privileges
     *
     * @param array  $formValues  The form values
     *
     * @return Response
     */
    public function update(array $formValues): Response
    {
        $this->response->dialog->hide();
        $this->response->dialog->warning("This feature is not yet implemented.");
        // $this->response->dialog->info("User privileges updated.");

        return $this->response;
    }
}
