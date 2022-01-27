<?php

namespace Lagdo\DbAdmin\App\Ajax;

use Jaxon\Response\Response;
use Lagdo\DbAdmin\App\CallableClass;

use Exception;

class User extends CallableClass
{
    /**
     * Show the new user form
     *
     * @param string $server      The database server
     *
     * @return Response
     */
    public function add(string $server): Response
    {
        $userInfo = $this->dbAdmin->newUserPrivileges($server);

        // Make user info available to views
        $this->view()->shareValues($userInfo);

        $formId = 'user-form';
        $title = 'Add user privileges';
        $privileges = $this->uiBuilder->mainContent($this->renderMainContent());
        $content = $this->uiBuilder->userForm($formId, $userInfo['user'], $privileges);
        // $this->response->html($this->package->getDbContentId(), $content);

        $buttons = [[
            'title' => 'Cancel',
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ],[
            'title' => 'Save',
            'class' => 'btn btn-primary',
            'click' => $this->rq()->create($server, \pm()->form($formId)),
        ]];
        $this->response->dialog->show($title, $content, $buttons);

        return $this->response;
    }

    /**
     * Save new user privileges
     *
     * @param string $server      The database server
     * @param array  $formValues  The form values
     *
     * @return Response
     */
    public function create(string $server, array $formValues): Response
    {
        // $this->logger()->debug('Form values', $formValues);

        $this->response->dialog->hide();
        $this->response->dialog->warning("This feature is not yet implemented.");
        // $this->response->dialog->info("User privileges created.");

        return $this->response;
    }

    /**
     * Show the edit user form
     *
     * @param string $server    The database server
     * @param string $username  The user name
     * @param string $hostname  The host name
     * @param string $database  The database name
     *
     * @return Response
     */
    public function edit(string $server, string $username, string $hostname, string $database): Response
    {
        $userInfo = $this->dbAdmin->getUserPrivileges($server, $username, $hostname, $database);

        // Make user info available to views
        $this->view()->shareValues($userInfo);

        $formId = 'user-form';
        $title = 'Edit user privileges';
        $privileges = $this->uiBuilder->mainContent($this->renderMainContent());
        $content = $this->uiBuilder->userForm($formId, $userInfo['user'], $privileges);
        // $this->response->html($this->package->getDbContentId(), $content);

        $buttons = [[
            'title' => 'Cancel',
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ],[
            'title' => 'Save',
            'class' => 'btn btn-primary',
            'click' => $this->rq()->update($server, \pm()->form($formId)),
        ]];
        $this->response->dialog->show($title, $content, $buttons);

        return $this->response;
    }

    /**
     * Update user privileges
     *
     * @param string $server      The database server
     * @param array  $formValues  The form values
     *
     * @return Response
     */
    public function update(string $server, array $formValues): Response
    {
        // $this->logger()->debug('Form values', $formValues);

        $this->response->dialog->hide();
        $this->response->dialog->warning("This feature is not yet implemented.");
        // $this->response->dialog->info("User privileges updated.");

        return $this->response;
    }
}
