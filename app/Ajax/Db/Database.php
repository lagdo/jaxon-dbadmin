<?php

namespace Lagdo\DbAdmin\App\Ajax\Db;

use Jaxon\Response\Response;
use Lagdo\DbAdmin\App\CallableDbClass;
use Lagdo\DbAdmin\App\Ajax\Db\Server\Databases;
use Lagdo\DbAdmin\App\Ajax\Menu\Db;
use Lagdo\DbAdmin\App\Ajax\Menu\DbActions;
use Lagdo\DbAdmin\App\Ajax\Menu\DbList;
use Lagdo\DbAdmin\App\Ajax\Menu\SchemaList;
use Lagdo\DbAdmin\App\Ajax\Page\Content;
use Lagdo\DbAdmin\App\Ajax\Page\PageActions;

use function count;
use function Jaxon\jq;
use function Jaxon\pm;

class Database extends CallableDbClass
{
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
        $this->cl(Databases::class)->update();

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
        [$server,] = $this->bag('dbadmin')->get('db');
        if(!$this->db->dropDatabase($database))
        {
            $this->response->dialog->error("Cannot delete database $database.");
            return $this->response;
        }

        $this->cl(Server::class)->showDatabases($server);
        $this->response->dialog->info("Database $database deleted.");
        return $this->response;
    }

    /**
     * Select a database
     *
     * @after('call' => 'showBreadcrumbs')
     * @after('call' => 'selectMenuItem', 'with' => ['.menu-action-table', 'adminer-database-menu'])
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
        $this->cl(DbList::class)->change($database);

        $schemas = $databaseInfo['schemas'];
        if(is_array($schemas) && count($schemas) > 0 && !$schema)
        {
            $schema = $schemas[0]; // Select the first schema

            $this->cl(SchemaList::class)->showDbSchemas($database, $schemas);
        }

        // Save the selection in the databag
        $this->bag('dbadmin')->set('db', [$server, $database, $schema]);

        $this->cl(DbActions::class)->render();

        $this->cl(Db::class)->showDatabase();

        // Show the database tables
        $this->showTables();

        return $this->response;
    }

    /**
     * Display the content of a section
     *
     * @param array $viewData  The data to be displayed in the view
     * @param string $checkbox
     *
     * @return void
     */
    protected function showSection(array $viewData, string $checkbox = '')
    {
        $content = $this->ui->mainContent($viewData, $checkbox);
        $this->cl(Content::class)->showHtml($content);
    }

    /**
     * Show the tables of a given database
     *
     * @after('call' => 'showBreadcrumbs')
     * @after('call' => 'selectMenuItem', 'with' => ['.menu-action-table', 'adminer-database-menu'])
     *
     * @return Response
     */
    public function showTables(): Response
    {
        // Set main menu buttons
        $this->cl(PageActions::class)->dbTables();

        $tablesInfo = $this->db->getTables();

        $table = jq()->parent()->attr('data-table-name');
        $select = $tablesInfo['select'];
        // Add links, classes and data values to table names.
        $tablesInfo['details'] = \array_map(function($detail) use($table, $select) {
            $tableName = $detail['name'];
            $detail['show'] = [
                'label' => $tableName,
                'props' => [
                    'data-table-name' => $tableName,
                ],
                'handler' => $this->rq(Table::class)->show($table),
            ];
            $detail['select'] = [
                'label' => $select,
                'props' => [
                    'data-table-name' => $tableName,
                ],
                'handler' => $this->rq(Table::class)->select($table),
            ];
            return $detail;
        }, $tablesInfo['details']);

        $this->showSection($tablesInfo, 'table');
        // Set onclick handlers on table checkbox
        $this->response->js('jaxon.dbadmin')->selectTableCheckboxes('table');

        return $this->response;
    }

    /**
     * Show the views of a given database
     *
     * @after('call' => 'showBreadcrumbs')
     * @after('call' => 'selectMenuItem', 'with' => ['.menu-action-view', 'adminer-database-menu'])
     *
     * @return Response
     */
    public function showViews(): Response
    {
        // Set main menu buttons
        $this->cl(PageActions::class)->dbTables();

        $viewsInfo = $this->db->getViews();

        $view = jq()->parent()->attr('data-view-name');
        // Add links, classes and data values to view names.
        $viewsInfo['details'] = \array_map(function($detail) use($view) {
            $detail['show'] = [
                'label' => $detail['name'],
                'props' => [
                    'data-view-name' => $detail['name'],
                ],
                'handler' => $this->rq(View::class)->show($view),
            ];
            return $detail;
        }, $viewsInfo['details']);

        $this->showSection($viewsInfo, 'view');

        // Set onclick handlers on view checkbox
        $this->response->js('jaxon.dbadmin')->selectTableCheckboxes('view');

        return $this->response;
    }

    /**
     * Show the routines of a given database
     *
     * @after('call' => 'showBreadcrumbs')
     * @after('call' => 'selectMenuItem', 'with' => ['.menu-action-routine', 'adminer-database-menu'])
     *
     * @return Response
     */
    public function showRoutines(): Response
    {
        // Set main menu buttons
        $this->cl(PageActions::class)->dbRoutines();

        $this->showSection($this->db->getRoutines());

        return $this->response;
    }

    /**
     * Show the sequences of a given database
     *
     * @after('call' => 'showBreadcrumbs')
     * @after('call' => 'selectMenuItem', 'with' => ['.menu-action-sequence', 'adminer-database-menu'])
     *
     * @return Response
     */
    public function showSequences(): Response
    {
        // Set main menu buttons
        $this->cl(PageActions::class)->dbSequences();

        $this->showSection($this->db->getSequences());

        return $this->response;
    }

    /**
     * Show the user types of a given database
     *
     * @after('call' => 'showBreadcrumbs')
     * @after('call' => 'selectMenuItem', 'with' => ['.menu-action-type', 'adminer-database-menu'])
     *
     * @return Response
     */
    public function showUserTypes(): Response
    {
        // Set main menu buttons
        $this->cl(PageActions::class)->dbUserTypes();

        $this->showSection($this->db->getUserTypes());

        return $this->response;
    }

    /**
     * Show the events of a given database
     *
     * @after('call' => 'showBreadcrumbs')
     * @after('call' => 'selectMenuItem', 'with' => ['.menu-action-event', 'adminer-database-menu'])
     *
     * @return Response
     */
    public function showEvents(): Response
    {
        // Set main menu buttons
        $this->cl(PageActions::class)->dbEvents();

        $this->showSection($this->db->getEvents());

        return $this->response;
    }
}
