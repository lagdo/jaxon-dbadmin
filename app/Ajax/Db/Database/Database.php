<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Database;

use Jaxon\Response\Response;
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
     * @return Response
     */
    public function select(string $database, string $schema = ''): Response
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
        $this->cl(Tables::class)->refresh();

        return $this->response;
    }

    /**
     * Show the  create database dialog
     *
     * @return Response
     */
    public function add(): Response
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
        $this->response->dialog->show($title, $content, $buttons);
        return $this->response;
    }

    /**
     * Show the  create database dialog
     *
     * @param array $formValues  The form values
     *
     * @return Response
     */
    public function create(array $formValues): Response
    {
        $database = $formValues['name'];
        $collation = $formValues['collation'];

        if(!$this->db->createDatabase($database, $collation))
        {
            $this->response->dialog->error("Cannot create database $database.");
            return $this->response;
        }
        $this->cl(Databases::class)->refresh();

        $this->response->dialog->hide();
        $this->response->dialog->info("Database $database created.");

        return $this->response;
    }

    /**
     * Drop a database
     *
     * @param string $database    The database name
     *
     * @return Response
     */
    public function drop(string $database): Response
    {
        if(!$this->db->dropDatabase($database))
        {
            $this->response->dialog->error("Cannot delete database $database.");
            return $this->response;
        }

        $this->cl(Databases::class)->refresh();

        $this->response->dialog->info("Database $database deleted.");
        return $this->response;
    }
}
