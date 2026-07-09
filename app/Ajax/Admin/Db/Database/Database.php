<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\Database;

use Lagdo\DbAdmin\App\Ajax\Admin\Db\FuncComponent;
use Lagdo\DbAdmin\App\Ajax\Admin\Db\Server\Databases;
use Lagdo\DbAdmin\App\Ui\Database\ServerUiBuilder;

use function Jaxon\form;

class Database extends FuncComponent
{
    /**
     * @param ServerUiBuilder $serverUi The HTML UI builder
     */
    public function __construct(protected ServerUiBuilder $serverUi)
    {}

    /**
     * Show the  create database dialog
     *
     * @return void
     */
    public function add(): void
    {
        $collations = $this->driver()->getCollations();

        $title = 'Create a database';
        $content = $this->serverUi->addDbForm($collations);
        $buttons = [[
            'title' => 'Cancel',
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ],[
            'title' => 'Save',
            'class' => 'btn btn-primary',
            'click' => $this->rq()->create(form($this->serverUi->dbFormId())),
        ]];
        $this->modal()->show($title, $content, $buttons);
    }

    /**
     * Show the  create database dialog
     *
     * @param array $formValues  The form values
     *
     * @return void
     */
    public function create(array $formValues): void
    {
        $database = $formValues['name'];
        $collation = $formValues['collation'];

        if(!$this->driver()->createDatabase($database, $collation))
        {
            $this->alert()->error("Cannot create database $database.");
            return;
        }

        $this->cl(Databases::class)->show();

        $this->modal()->hide();
        $this->alert()->info("Database $database created.");
    }

    /**
     * Drop a database
     *
     * @param string $database    The database name
     *
     * @return void
     */
    public function drop(string $database): void
    {
        if(!$this->driver()->dropDatabase($database))
        {
            $this->alert()->error("Cannot delete database $database.");
            return;
        }

        $this->cl(Databases::class)->show();

        $this->alert()->info("Database $database deleted.");
    }
}
