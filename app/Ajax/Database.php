<?php

namespace Lagdo\DbAdmin\App\Ajax;

use Jaxon\Response\Response;
use Lagdo\DbAdmin\App\CallableClass;
use Lagdo\DbAdmin\App\Ajax\Table\Select;

use Exception;

/**
 * Adminer Ajax client
 */
class Database extends CallableClass
{
    /**
     * Show the  create database dialog
     *
     * @param string $server      The database server
     *
     * @return Response
     */
    public function add(string $server): Response
    {
        $collations = $this->dbAdmin->getCollations($server);

        $formId = 'database-form';
        $title = 'Create a database';
        $content = $this->uiBuilder->addDbForm($formId, $collations);
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
     * Show the  create database dialog
     *
     * @param string $server      The database server
     * @param array $formValues  The form values
     *
     * @return Response
     */
    public function create(string $server, array $formValues): Response
    {
        $database = $formValues['name'];
        $collation = $formValues['collation'];

        if(!$this->dbAdmin->createDatabase($server, $database, $collation))
        {
            $this->response->dialog->error("Cannot create database $database.");
            return $this->response;
        }
        $this->cl(Server::class)->showDatabases($server);

        $this->response->dialog->hide();
        $this->response->dialog->info("Database $database created.");

        return $this->response;
    }

    /**
     * Drop a database
     *
     * @param string $server      The database server
     * @param string $database    The database name
     *
     * @return Response
     */
    public function drop(string $server, string $database): Response
    {
        if(!$this->dbAdmin->dropDatabase($server, $database))
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
     * @param string $server      The database server
     * @param string $database    The database name
     * @param string $schema      The database schema
     *
     * @return Response
     */
    public function select(string $server, string $database, string $schema = ''): Response
    {
        $databaseInfo = $this->dbAdmin->getDatabaseInfo($server, $database);
        // Make database info available to views
        $this->view()->shareValues($databaseInfo);

        // Set main menu buttons
        $content = isset($databaseInfo['mainActions']) ?
            $this->uiBuilder->mainActions($databaseInfo['mainActions']) : '';
        $this->response->html($this->package->getMainActionsId(), $content);

        // Set the selected entry on database dropdown select
        $this->jq('#adminer-dbname-select')->val($database)->change();

        $schemas = $databaseInfo['schemas'];
        if(is_array($schemas) && count($schemas) > 0 && !$schema)
        {
            $schema = $schemas[0]; // Select the first schema

            $content = $this->uiBuilder->menuSchemas($schemas);
            $this->response->html($this->package->getSchemaListId(), $content);

            $this->jq('#adminer-schema-select-btn')
                ->click($this->rq()->select($server, $database, \pm()->select('adminer-schema-select')));
        }

        $content = $this->uiBuilder->menuCommands($databaseInfo['sqlActions']);
        $this->response->html($this->package->getDbActionsId(), $content);

        // Set the click handlers
        $this->jq('#adminer-menu-action-database-command')
            ->click($this->cl(Command::class)->rq()->showDatabaseForm($server, $database, $schema));
        $this->jq('#adminer-menu-action-database-import')
            ->click($this->cl(Import::class)->rq()->showDatabaseForm($server, $database));
        $this->jq('#adminer-menu-action-database-export')
            ->click($this->cl(Export::class)->rq()->showDatabaseForm($server, $database));

        $content = $this->uiBuilder->menuActions($databaseInfo['menuActions']);
        $this->response->html($this->package->getDbMenuId(), $content);

        // Set the click handlers
        $this->jq('#adminer-menu-action-table')
            ->click($this->rq()->showTables($server, $database, $schema));
        $this->jq('#adminer-menu-action-view')
            ->click($this->rq()->showViews($server, $database, $schema));
        $this->jq('#adminer-menu-action-routine')
            ->click($this->rq()->showRoutines($server, $database, $schema));
        $this->jq('#adminer-menu-action-sequence')
            ->click($this->rq()->showSequences($server, $database, $schema));
        $this->jq('#adminer-menu-action-type')
            ->click($this->rq()->showUserTypes($server, $database, $schema));
        $this->jq('#adminer-menu-action-event')
            ->click($this->rq()->showEvents($server, $database, $schema));

        // Show the database tables
        $this->showTables($server, $database, $schema);

        return $this->response;
    }

    /**
     * Display the content of a section
     *
     * @param array  $viewData  The data to be displayed in the view
     * @param array  $contentData  The data to be displayed in the view
     *
     * @return void
     */
    protected function showSection(array $viewData, array $contentData = [])
    {
        // Make data available to views
        $this->view()->shareValues($viewData);

        // Set main menu buttons
        $content = isset($viewData['mainActions']) ?
            $this->uiBuilder->mainActions($viewData['mainActions']) : '';
        $this->response->html($this->package->getMainActionsId(), $content);

        $counterId = $contentData['checkbox'] ?? '';
        $content = $this->uiBuilder->mainContent($this->renderMainContent($contentData), $counterId);
        $this->response->html($this->package->getDbContentId(), $content);
    }

    /**
     * Show the tables of a given database
     *
     * @param string $server      The database server
     * @param string $database    The database name
     * @param string $schema      The database schema
     *
     * @return Response
     */
    public function showTables(string $server, string $database, string $schema): Response
    {
        $tablesInfo = $this->dbAdmin->getTables($server, $database, $schema);

        $tableNameClass = 'adminer-table-name';
        $select = $tablesInfo['select'];
        // Add links, classes and data values to table names.
        $tablesInfo['details'] = \array_map(function($detail) use($tableNameClass, $select) {
            $detail['name'] = [
                'label' => '<a class="name" href="javascript:void(0)">' . $detail['name'] . '</a>' .
                    '&nbsp;&nbsp;(<a class="select" href="javascript:void(0)">' . $select . '</a>)',
                'props' => [
                    'class' => $tableNameClass,
                    'data-name' => $detail['name'],
                ],
            ];
            return $detail;
        }, $tablesInfo['details']);

        $checkbox = 'table';
        $this->showSection($tablesInfo, ['checkbox' => $checkbox]);

        // Set onclick handlers on toolbar buttons
        $this->jq('#adminer-main-action-add-table')
            ->click($this->cl(Table::class)->rq()->add($server, $database, $schema));

        // Set onclick handlers on table checkbox
        $this->response->script("jaxon.adminer.selectTableCheckboxes('$checkbox')");
        // Set onclick handlers on table names
        $table = \jq()->parent()->attr('data-name');
        $this->jq('.' . $tableNameClass . '>a.name', '#' . $this->package->getDbContentId())
            ->click($this->cl(Table::class)->rq()->show($server, $database, $schema, $table));
        $this->jq('.' . $tableNameClass . '>a.select', '#' . $this->package->getDbContentId())
            ->click($this->cl(Select::class)->rq()->show($server, $database, $schema, $table));

        return $this->response;
    }

    /**
     * Show the views of a given database
     *
     * @param string $server      The database server
     * @param string $database    The database name
     * @param string $schema      The database schema
     *
     * @return Response
     */
    public function showViews(string $server, string $database, string $schema): Response
    {
        $viewsInfo = $this->dbAdmin->getViews($server, $database, $schema);

        $viewNameClass = 'adminer-view-name';
        // Add links, classes and data values to view names.
        $viewsInfo['details'] = \array_map(function($detail) use($viewNameClass) {
            $detail['name'] = [
                'label' => '<a href="javascript:void(0)">' . $detail['name'] . '</a>',
                'props' => [
                    'class' => $viewNameClass,
                    'data-name' => $detail['name'],
                ],
            ];
            return $detail;
        }, $viewsInfo['details']);

        $checkbox = 'view';
        $this->showSection($viewsInfo, ['checkbox' => $checkbox]);

        // Set onclick handlers on toolbar buttons
        $this->jq('#adminer-main-action-add-view')
            ->click($this->cl(View::class)->rq()->add($server, $database, $schema));

        // Set onclick handlers on view checkbox
        $this->response->script("jaxon.adminer.selectTableCheckboxes('$checkbox')");
        // Set onclick handlers on view names
        $view = \jq()->parent()->attr('data-name');
        $this->jq('.' . $viewNameClass . '>a', '#' . $this->package->getDbContentId())
            ->click($this->cl(View::class)->rq()->show($server, $database, $schema, $view));

        return $this->response;
    }

    /**
     * Show the routines of a given database
     *
     * @param string $server      The database server
     * @param string $database    The database name
     * @param string $schema      The database schema
     *
     * @return Response
     */
    public function showRoutines(string $server, string $database, string $schema): Response
    {
        $routinesInfo = $this->dbAdmin->getRoutines($server, $database, $schema);
        $this->showSection($routinesInfo);

        return $this->response;
    }

    /**
     * Show the sequences of a given database
     *
     * @param string $server      The database server
     * @param string $database    The database name
     * @param string $schema      The database schema
     *
     * @return Response
     */
    public function showSequences(string $server, string $database, string $schema): Response
    {
        $sequencesInfo = $this->dbAdmin->getSequences($server, $database, $schema);
        $this->showSection($sequencesInfo);

        return $this->response;
    }

    /**
     * Show the user types of a given database
     *
     * @param string $server      The database server
     * @param string $database    The database name
     * @param string $schema      The database schema
     *
     * @return Response
     */
    public function showUserTypes(string $server, string $database, string $schema): Response
    {
        $userTypesInfo = $this->dbAdmin->getUserTypes($server, $database, $schema);
        $this->showSection($userTypesInfo);

        return $this->response;
    }

    /**
     * Show the events of a given database
     *
     * @param string $server      The database server
     * @param string $database    The database name
     * @param string $schema      The database schema
     *
     * @return Response
     */
    public function showEvents(string $server, string $database, string $schema): Response
    {
        $eventsInfo = $this->dbAdmin->getEvents($server, $database, $schema);
        $this->showSection($eventsInfo);

        return $this->response;
    }
}
