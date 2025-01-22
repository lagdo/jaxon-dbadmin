<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Database;

use Lagdo\DbAdmin\App\CallableDbClass;
use Lagdo\DbAdmin\App\Ajax\Db\Server\Databases;
use Lagdo\DbAdmin\App\Ajax\Menu\Sections as MenuSections;
use Lagdo\DbAdmin\App\Ajax\Menu\Database\Command as DatabaseCommand;
use Lagdo\DbAdmin\App\Ajax\Menu\Database\Schemas as MenuSchemas;
use Lagdo\DbAdmin\App\Ajax\Menu\Server\Databases as MenuDatabases;
use Lagdo\DbAdmin\App\Ajax\Page\PageActions;

use function count;
use function is_array;
use function Jaxon\pm;

class Database extends CallableDbClass
{
    /**
     * Select a database
     *
     * @after showBreadcrumbs
     *
     * @param string $database    The database name
     * @param string $schema      The database schema
     *
     * @return void
     */
    public function select(string $database, string $schema = '')
    {
        [$server,] = $this->bag('dbadmin')->get('db');
        // Set the selected server
        $this->db->selectDatabase($server, $database);

        $databaseInfo = $this->db->getDatabaseInfo();
        // Make database info available to views
        $this->view()->shareValues($databaseInfo);

        // Set main menu buttons
        $this->cl(PageActions::class)->clear();

        // Set the selected entry on database dropdown select
        $this->cl(MenuDatabases::class)->change($database);

        $schemas = $databaseInfo['schemas'];
        if(is_array($schemas) && count($schemas) > 0 && !$schema)
        {
            $schema = $schemas[0]; // Select the first schema

            $this->cl(MenuSchemas::class)->showDbSchemas($database, $schemas);
        }

        // Save the selection in the databag
        $this->bag('dbadmin')->set('db', [$server, $database, $schema]);

        $this->cl(DatabaseCommand::class)->render();

        // Show the database tables
        $this->cl(Tables::class)->show();
    }

    /**
     * Show the  create database dialog
     *
     * @return void
     */
    public function add()
    {
        $collations = $this->db->getCollations();

        $formId = 'database-form';
        $title = 'Create a database';
        $content = $this->ui->addDbForm($formId, $collations);
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
     * Show the  create database dialog
     *
     * @param array $formValues  The form values
     *
     * @return void
     */
    public function create(array $formValues)
    {
        $database = $formValues['name'];
        $collation = $formValues['collation'];

        if(!$this->db->createDatabase($database, $collation))
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
    public function drop(string $database)
    {
        if(!$this->db->dropDatabase($database))
        {
            $this->alert()->error("Cannot delete database $database.");
            return;
        }

        $this->cl(Databases::class)->show();

        $this->alert()->info("Database $database deleted.");
    }
}
