<?php

namespace Lagdo\Adminer\Ajax;

use Lagdo\Adminer\AdminerCallable;

use Exception;

/**
 * Adminer Ajax client
 */
class Database extends AdminerCallable
{
    /**
     * Show the  create database dialog
     *
     * @param string $server      The database server
     *
     * @return \Jaxon\Response\Response
     */
    public function add($server)
    {
        $collations = $this->dbProxy->getCollations($server);

        $formId = 'database-form';
        $title = 'Create a database';
        $content = $this->render('database/add', [
            'formId' => $formId,
            'collations' => $collations,
        ]);
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
     * @param string $formValues  The form values
     *
     * @return \Jaxon\Response\Response
     */
    public function create($server, array $formValues)
    {
        $database = $formValues['name'];
        $collation = $formValues['collation'];

        if(!$this->dbProxy->createDatabase($server, $database, $collation))
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
     * @return \Jaxon\Response\Response
     */
    public function drop($server, $database)
    {
        if(!$this->dbProxy->dropDatabase($server, $database))
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
     *
     * @return \Jaxon\Response\Response
     */
    public function select($server, $database)
    {
        $databaseInfo = $this->dbProxy->getDatabaseInfo($server, $database);
        // Make database info available to views
        foreach($databaseInfo as $name => $value)
        {
            $this->view()->share($name, $value);
        }

        // Update the breadcrumbs
        $this->showBreadcrumbs();

        $content = $this->render('menu/commands');
        $this->response->html($this->package->getServerActionsId(), '');
        $this->response->html($this->package->getDbActionsId(), $content);

        // Set the click handlers
        $this->jq('#adminer-menu-action-database-command')
            ->click($this->cl(Command::class)->rq()->showCommandForm($server, $database));
        $this->jq('#adminer-menu-action-database-import')
            ->click($this->cl(Import::class)->rq()->showImportForm($server, $database));
        $this->jq('#adminer-menu-action-database-export')
            ->click($this->cl(Export::class)->rq()->showExportForm($server, $database));

        $content = $this->render('menu/actions');
        $this->response->html($this->package->getDbMenuId(), $content);

        // Set the click handlers
        $this->jq('#adminer-menu-action-table')
            ->click($this->rq()->showTables($server, $database));
        $this->jq('#adminer-menu-action-view')
            ->click($this->rq()->showViews($server, $database));
        $this->jq('#adminer-menu-action-routine')
            ->click($this->rq()->showRoutines($server, $database));
        $this->jq('#adminer-menu-action-sequence')
            ->click($this->rq()->showSequences($server, $database));
        $this->jq('#adminer-menu-action-type')
            ->click($this->rq()->showUserTypes($server, $database));
        $this->jq('#adminer-menu-action-event')
            ->click($this->rq()->showEvents($server, $database));
        // Set the selected entry on database dropdown select
        $this->jq('#adminer-dbname-select')->val($database)->change();

        // Show the database tables
        $this->showTables($server, $database);

        return $this->response;
    }

    /**
     * Display the content of a section
     *
     * @param string $menuId    The menu item id
     * @param array  $viewData  The data to be displayed in the view
     * @param array  $contentData  The data to be displayed in the view
     *
     * @return void
     */
    protected function showSection(string $menuId, array $viewData, array $contentData = [])
    {
        // Make data available to views
        foreach($viewData as $name => $value)
        {
            $this->view()->share($name, $value);
        }

        // Update the breadcrumbs
        $this->showBreadcrumbs();

        $content = $this->render('main/content', $contentData);
        $this->response->html($this->package->getDbContentId(), $content);

        // Activate the sidebar menu item
        $this->jq('.list-group-item', '#'. $this->package->getDbMenuId())->removeClass('active');
        $this->jq(".menu-action-$menuId", '#'. $this->package->getDbMenuId())->addClass('active');
    }

    /**
     * Show the tables of a given database
     *
     * @param string $server      The database server
     * @param string $database    The database name
     *
     * @return \Jaxon\Response\Response
     */
    public function showTables($server, $database)
    {
        $tablesInfo = $this->dbProxy->getTables($server, $database);

        $tableNameClass = 'adminer-table-name';
        // Add links, classes and data values to table names.
        $tablesInfo['details'] = \array_map(function($detail) use($tableNameClass) {
            $detail['name'] = [
                'label' => '<a href="javascript:void(0)">' . $detail['name'] . '</a>',
                'props' => [
                    'class' => $tableNameClass,
                    'data-name' => $detail['name'],
                ],
            ];
            return $detail;
        }, $tablesInfo['details']);

        $checkbox = 'table';
        $this->showSection('table', $tablesInfo, ['checkbox' => $checkbox]);

        // Set onclick handlers on toolbar buttons
        $this->jq('#adminer-main-action-add-table')
            ->click($this->cl(Table::class)->rq()->add($server, $database));

        // Set onclick handlers on table checkbox
        $this->response->script("jaxon.adminer.selectTableCheckboxes('$checkbox')");
        // Set onclick handlers on table names
        $table = \jq()->parent()->attr('data-name');
        $this->jq('.' . $tableNameClass . '>a', '#' . $this->package->getDbContentId())
            ->click($this->cl(Table::class)->rq()->show($server, $database, $table));

        return $this->response;
    }

    /**
     * Show the views of a given database
     *
     * @param string $server      The database server
     * @param string $database    The database name
     *
     * @return \Jaxon\Response\Response
     */
    public function showViews($server, $database)
    {
        $viewsInfo = $this->dbProxy->getViews($server, $database);

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
        $this->showSection('view', $viewsInfo, ['checkbox' => $checkbox]);

        // Set onclick handlers on toolbar buttons
        $this->jq('#adminer-main-action-add-view')
            ->click($this->cl(View::class)->rq()->add($server, $database));

        // Set onclick handlers on view checkbox
        $this->response->script("jaxon.adminer.selectTableCheckboxes('$checkbox')");
        // Set onclick handlers on view names
        $view = \jq()->parent()->attr('data-name');
        $this->jq('.' . $viewNameClass . '>a', '#' . $this->package->getDbContentId())
            ->click($this->cl(View::class)->rq()->show($server, $database, $view));

        return $this->response;
    }

    /**
     * Show the routines of a given database
     *
     * @param string $server      The database server
     * @param string $database    The database name
     *
     * @return \Jaxon\Response\Response
     */
    public function showRoutines($server, $database)
    {
        $routinesInfo = $this->dbProxy->getRoutines($server, $database);
        $this->showSection('routine', $routinesInfo);

        return $this->response;
    }

    /**
     * Show the sequences of a given database
     *
     * @param string $server      The database server
     * @param string $database    The database name
     *
     * @return \Jaxon\Response\Response
     */
    public function showSequences($server, $database)
    {
        $sequencesInfo = $this->dbProxy->getSequences($server, $database);
        $this->showSection('sequence', $sequencesInfo);

        return $this->response;
    }

    /**
     * Show the user types of a given database
     *
     * @param string $server      The database server
     * @param string $database    The database name
     *
     * @return \Jaxon\Response\Response
     */
    public function showUserTypes($server, $database)
    {
        $userTypesInfo = $this->dbProxy->getUserTypes($server, $database);
        $this->showSection('type', $userTypesInfo);

        return $this->response;
    }

    /**
     * Show the events of a given database
     *
     * @param string $server      The database server
     * @param string $database    The database name
     *
     * @return \Jaxon\Response\Response
     */
    public function showEvents($server, $database)
    {
        $eventsInfo = $this->dbProxy->getEvents($server, $database);
        $this->showSection('event', $eventsInfo);

        return $this->response;
    }
}