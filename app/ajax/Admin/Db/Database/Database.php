<?php

namespace Lagdo\DbAdmin\Ajax\Admin\Db\Database;

use Jaxon\Attributes\Attribute\After;
use Jaxon\Attributes\Attribute\Before;
use Lagdo\DbAdmin\Ajax\Admin\Db\FuncComponent;
use Lagdo\DbAdmin\Ajax\Admin\Db\Server\Databases;
use Lagdo\DbAdmin\Ajax\Admin\Menu\Database\Schemas as MenuSchemas;
use Lagdo\DbAdmin\Ajax\Admin\Menu\Server\Databases as MenuDatabases;
use Lagdo\DbAdmin\Ajax\Admin\Page\PageActions;
use Lagdo\DbAdmin\Db\Config\ServerConfig;
use Lagdo\DbAdmin\Db\Driver\DbFacade;
use Lagdo\DbAdmin\Db\Translator;
use Lagdo\DbAdmin\Ui\Database\ServerUiBuilder;

use function count;
use function is_array;
use function Jaxon\form;

class Database extends FuncComponent
{
    /**
     * The constructor
     *
     * @param ServerConfig    $config     The package config
     * @param DbFacade        $db         The facade to database functions
     * @param ServerUiBuilder $serverUi The HTML UI builder
     * @param Translator      $trans
     */
    public function __construct(protected ServerConfig $config, protected DbFacade $db,
        protected ServerUiBuilder $serverUi, protected Translator $trans)
    {}

    /**
     * Select a database
     *
     * @param string $database    The database name
     * @param string $schema      The database schema
     *
     * @return void
     */
    #[After('showBreadcrumbs')]
    public function select(string $database, string $schema = ''): void
    {
        [$server,] = $this->bag('dbadmin')->get('db');
        // Set the selected server
        $this->db()->selectDatabase($server, $database);

        $systemAccess = $this->config()->getOption('access.system', false);
        $databaseInfo = $this->db()->getDatabaseInfo($systemAccess);

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

        $this->db()->selectDatabase($server, $database, $schema);

        // Save the selection in the databag
        $this->bag('dbadmin')->set('db', [$server, $database, $schema]);

        // Show the database tables
        $this->cl(Tables::class)->show();
    }

    /**
     * Show the  create database dialog
     *
     * @return void
     */
    #[Before('notYetAvailable')]
    public function add(): void
    {
        $collations = $this->db()->getCollations();

        $formId = 'database-form';
        $title = 'Create a database';
        $content = $this->serverUi->addDbForm($formId, $collations);
        $buttons = [[
            'title' => 'Cancel',
            'class' => 'btn btn-tertiary',
            'click' => 'close',
        ],[
            'title' => 'Save',
            'class' => 'btn btn-primary',
            'click' => $this->rq()->create(form($formId)),
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
    #[Before('notYetAvailable')]
    public function create(array $formValues): void
    {
        $database = $formValues['name'];
        $collation = $formValues['collation'];

        if(!$this->db()->createDatabase($database, $collation))
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
    #[Before('notYetAvailable')]
    public function drop(string $database): void
    {
        if(!$this->db()->dropDatabase($database))
        {
            $this->alert()->error("Cannot delete database $database.");
            return;
        }

        $this->cl(Databases::class)->show();

        $this->alert()->info("Database $database deleted.");
    }
}
