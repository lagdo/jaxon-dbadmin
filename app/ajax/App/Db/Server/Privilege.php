<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Server;

use Lagdo\DbAdmin\Ajax\App\Db\FuncComponent;
use Lagdo\DbAdmin\Db\DbFacade;
use Lagdo\DbAdmin\Package;
use Lagdo\DbAdmin\Translator;
use Lagdo\DbAdmin\Ui\Database\ServerUiBuilder;

use function Jaxon\je;

/**
 * @before notYetAvailable
 */
class Privilege extends FuncComponent
{
    /**
     * The constructor
     *
     * @param Package       $package    The DbAdmin package
     * @param DbFacade      $db         The facade to database functions
     * @param ServerUiBuilder $serverUi The HTML UI builder
     * @param Translator    $trans
     */
    public function __construct(protected Package $package, protected DbFacade $db,
        protected ServerUiBuilder $serverUi, protected Translator $trans)
    {}

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
        $privileges = $this->serverUi->pageContent($userInfo);
        $content = $this->serverUi->addUserForm($formId, $userInfo['user'], $privileges);

        $buttons = [[
            'title' => 'Cancel',
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ],[
            'title' => 'Save',
            'class' => 'btn btn-primary',
            'click' => $this->rq()->create(je($formId)->rd()->form()),
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
        $privileges = $this->serverUi->pageContent($userInfo);
        $content = $this->serverUi->addUserForm($formId, $userInfo['user'], $privileges);

        $buttons = [[
            'title' => 'Cancel',
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ],[
            'title' => 'Save',
            'class' => 'btn btn-primary',
            'click' => $this->rq()->update(je($formId)->rd()->form()),
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
