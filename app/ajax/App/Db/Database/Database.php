<?php

namespace Lagdo\DbAdmin\Ajax\App\Db\Database;

use Lagdo\DbAdmin\Ajax\App\Db\FuncComponent;
use Lagdo\DbAdmin\Ajax\App\Db\Server\Databases;
use Lagdo\DbAdmin\Ajax\App\Menu\Database\Schemas as MenuSchemas;
use Lagdo\DbAdmin\Ajax\App\Menu\Server\Databases as MenuDatabases;
use Lagdo\DbAdmin\Ajax\App\Page\PageActions;
use Lagdo\DbAdmin\Db\DbFacade;
use Lagdo\DbAdmin\Package;
use Lagdo\DbAdmin\Translator;
use Lagdo\DbAdmin\Ui\Database\ServerUiBuilder;

use function count;
use function is_array;
use function Jaxon\je;

class Database extends FuncComponent
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
     * Select a database
     *
     * @after showBreadcrumbs
     *
     * @param string $database    The database name
     * @param string $schema      The database schema
     *
     * @return void
     */
    public function select(string $database, string $schema = ''): void
    {
        [$server,] = $this->bag('dbadmin')->get('db');
        // Set the selected server
        $this->db()->selectDatabase($server, $database);

        $systemAccess = $this->package()->getOption('access.system', false);
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

        // Save the selection in the databag
        $this->bag('dbadmin')->set('db', [$server, $database, $schema]);

        // Show the database tables
        $this->cl(Tables::class)->show();
    }

    /**
     * Show the  create database dialog
     * @before notYetAvailable
     *
     * @return void
     */
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
            'click' => $this->rq()->create(je($formId)->rd()->form()),
        ]];
        $this->modal()->show($title, $content, $buttons);
    }

    /**
     * Show the  create database dialog
     * @before notYetAvailable
     *
     * @param array $formValues  The form values
     *
     * @return void
     */
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
     * @before notYetAvailable
     *
     * @param string $database    The database name
     *
     * @return void
     */
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
