<?php

namespace Lagdo\DbAdmin\App\Ajax\Db;

use Jaxon\Response\Response;
use Lagdo\DbAdmin\App\CallableDbClass;

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
        $this->cl(Server::class)->showDatabases();

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
        $content = isset($databaseInfo['mainActions']) ?
            $this->ui->mainActions($databaseInfo['mainActions']) : '';
        $this->response->html($this->package->getMainActionsId(), $content);

        // Set the selected entry on database dropdown select
        $this->jq('#adminer-dbname-select')->val($database)->change();

        $schemas = $databaseInfo['schemas'];
        if(is_array($schemas) && count($schemas) > 0 && !$schema)
        {
            $schema = $schemas[0]; // Select the first schema

            $content = $this->ui->menuSchemas($schemas);
            $this->response->html($this->package->getSchemaListId(), $content);

            $this->jq('#adminer-schema-select-btn')
                ->click($this->rq()->select($database, pm()->select('adminer-schema-select')));
        }

        // Save the selection in the databag
        $this->bag('dbadmin')->set('db', [$server, $database, $schema]);

        $content = $this->ui->menuCommands($databaseInfo['sqlActions']);
        $this->response->html($this->package->getDbActionsId(), $content);

        // Set the click handlers
        $this->jq('#adminer-menu-action-database-command')
            ->click($this->rq(Command::class)->showDatabaseForm());
        $this->jq('#adminer-menu-action-database-import')
            ->click($this->rq(Import::class)->showDatabaseForm());
        $this->jq('#adminer-menu-action-database-export')
            ->click($this->rq(Export::class)->showDatabaseForm());

        $content = $this->ui->menuActions($databaseInfo['menuActions']);
        $this->response->html($this->package->getDbMenuId(), $content);

        // Set the click handlers
        $this->jq('#adminer-menu-action-table')->click($this->rq()->showTables());
        $this->jq('#adminer-menu-action-view')->click($this->rq()->showViews());
        $this->jq('#adminer-menu-action-routine')->click($this->rq()->showRoutines());
        $this->jq('#adminer-menu-action-sequence')->click($this->rq()->showSequences());
        $this->jq('#adminer-menu-action-type')->click($this->rq()->showUserTypes());
        $this->jq('#adminer-menu-action-event')->click($this->rq()->showEvents());

        // Show the database tables
        $this->showTables();

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
            $this->ui->mainActions($viewData['mainActions']) : '';
        $this->response->html($this->package->getMainActionsId(), $content);

        $counterId = $contentData['checkbox'] ?? '';
        $content = $this->ui->mainContent($this->renderMainContent($contentData), $counterId);
        $this->response->html($this->package->getDbContentId(), $content);
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
        $tablesInfo = $this->db->getTables();

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
        $this->jq('#adminer-main-action-add-table')->click($this->rq(Table::class)->add());

        // Set onclick handlers on table checkbox
        $this->response->call("jaxon.dbadmin.selectTableCheckboxes", $checkbox);
        // Set onclick handlers on table names
        $table = jq()->parent()->attr('data-name');
        $this->jq('.' . $tableNameClass . '>a.name', '#' . $this->package->getDbContentId())
            ->click($this->rq(Table::class)->show($table));
        $this->jq('.' . $tableNameClass . '>a.select', '#' . $this->package->getDbContentId())
            ->click($this->rq(Table::class)->select($table));

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
        $viewsInfo = $this->db->getViews();

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
        $this->jq('#adminer-main-action-add-view')->click($this->rq(View::class)->add());

        // Set onclick handlers on view checkbox
        $this->response->call("jaxon.dbadmin.selectTableCheckboxes", $checkbox);
        // Set onclick handlers on view names
        $view = jq()->parent()->attr('data-name');
        $this->jq('.' . $viewNameClass . '>a', '#' . $this->package->getDbContentId())
            ->click($this->rq(View::class)->show($view));

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
        $routinesInfo = $this->db->getRoutines();
        $this->showSection($routinesInfo);

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
        $sequencesInfo = $this->db->getSequences();
        $this->showSection($sequencesInfo);

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
        $userTypesInfo = $this->db->getUserTypes();
        $this->showSection($userTypesInfo);

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
        $eventsInfo = $this->db->getEvents();
        $this->showSection($eventsInfo);

        return $this->response;
    }
}
