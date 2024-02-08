<?php

namespace Lagdo\DbAdmin\App\Ajax\Db;

use Jaxon\Response\Response;
use Lagdo\DbAdmin\App\CallableDbClass;
use Lagdo\DbAdmin\Db\Exception\DbException;

use function array_values;
use function count;
use function Jaxon\jq;
use function Jaxon\pm;

/**
 * @before('call' => 'checkServerAccess')
 */
class Server extends CallableDbClass
{
    /**
     * Check if the user has access to a server
     *
     * @return void
     */
    protected function checkServerAccess()
    {
        if($this->target()->method() === 'connect')
        {
            return; // No check for the connect() method.
        }
        if(!$this->package->getServerAccess($this->db->getCurrentServer()))
        {
            throw new DbException('Access to server data is forbidden');
        }
    }

    /**
     * Show the database dropdown list.
     *
     * @return array
     */
    protected function showDatabaseMenu(): array
    {
        // Access to servers is forbidden. Show the first database.
        $databasesInfo = $this->db->getDatabases();

        // Make databases info available to views
        $this->view()->shareValues($databasesInfo);

        // Set the database dropdown list
        $content = $this->ui->menuDatabases($databasesInfo['databases']);
        $this->response->html($this->package->getDbListId(), $content);

        // Hide schema list
        // $this->response->assign($this->package->getSchemaListId(), 'style.display', 'none');
        $this->response->clear($this->package->getSchemaListId());

        // Set onclick handlers on database dropdown select
        $database = pm()->select('adminer-dbname-select');
        $this->jq('#adminer-dbname-select-btn')
            ->click($this->rq(Database::class)->select($database)->when($database));

        return $databasesInfo;
    }

    /**
     * Connect to a db server.
     * The database list will be displayed in the HTML select component.
     *
     * @exclude
     *
     * @param bool $hasServerAccess
     *
     * @return Response
     */
    public function connect(bool $hasServerAccess): Response
    {
        $serverInfo = $this->db->getServerInfo();
        // Make server info available to views
        $this->view()->shareValues($serverInfo);

        $content = $this->ui->serverInfo($serverInfo['server'], $serverInfo['user']);
        $this->response->html($this->package->getServerInfoId(), $content);

        // Show the server
        $content = $this->ui->menuCommands($serverInfo['sqlActions']);
        $this->response->html($this->package->getServerActionsId(), $content);
        $this->response->html($this->package->getDbActionsId(), '');

        // Set the click handlers
        $this->jq('#adminer-menu-action-server-command')
            ->click($this->rq(Command::class)->showServerForm());
        $this->jq('#adminer-menu-action-server-import')
            ->click($this->rq(Import::class)->showServerForm());
        $this->jq('#adminer-menu-action-server-export')
            ->click($this->rq(Export::class)->showServerForm());

        $content = $this->ui->menuActions($serverInfo['menuActions']);
        $this->response->html($this->package->getDbMenuId(), $content);

        if(!$hasServerAccess)
        {
            $databasesInfo = $this->showDatabaseMenu();
            if(count($databasesInfo['databases']) > 0)
            {
                $database = array_values($databasesInfo['databases'])[0];
                $this->cl(Database::class)->select($database);
                $this->selectMenuItem('.menu-action-table', 'adminer-database-menu');
            }

            return $this->response;
        }

        // Set the click handlers
        $this->jq('#adminer-menu-action-databases')->click($this->rq()->showDatabases());
        $this->jq('#adminer-menu-action-privileges')->click($this->rq()->showPrivileges());
        $this->jq('#adminer-menu-action-processes')->click($this->rq()->showProcesses());
        $this->jq('#adminer-menu-action-variables')->click($this->rq()->showVariables());
        $this->jq('#adminer-menu-action-status')->click($this->rq()->showStatus());

        // Show the database list
        $this->selectMenuItem('.menu-action-databases', 'adminer-database-menu');
        return $this->showDatabases();
    }

    /**
     * Show the databases of a server
     *
     * @after('call' => 'showBreadcrumbs')
     * @after('call' => 'selectMenuItem', 'with' => ['.menu-action-databases', 'adminer-database-menu'])
     *
     * @return Response
     */
    public function showDatabases(): Response
    {
        $databasesInfo = $this->showDatabaseMenu();

        $dbNameClass = 'adminer-database-name';
        $dbDropClass = 'adminer-database-drop';
        // Add links, classes and data values to database names.
        $details = \array_map(function($detail) use($dbNameClass, $dbDropClass) {
            $name = $detail['name'];
            $detail['name'] = [
                'label' => '<a href="javascript:void(0)">' . $name . '</a>',
                'props' => [
                    'class' => $dbNameClass,
                    'data-name' => $name,
                ],
            ];
            $detail['drop'] = [
                'label' => '<a href="javascript:void(0)">Drop</a>',
                'props' => [
                    'class' => $dbDropClass,
                    'data-name' => $name,
                ],
            ];
            return $detail;
        }, $databasesInfo['details']);
        $this->view()->share('details', $details);

        // Set main menu buttons
        $content = isset($databasesInfo['mainActions']) ?
            $this->ui->mainActions($databasesInfo['mainActions']) : '';
        $this->response->html($this->package->getMainActionsId(), $content);

        // Add checkboxes to database table
        $checkbox = 'database';
        $content = $this->ui->mainContent($this->renderMainContent(['checkbox' => $checkbox]), $checkbox);
        $this->response->html($this->package->getDbContentId(), $content);

        // Set onclick handlers on table checkbox
        $this->response->call("jaxon.dbadmin.selectTableCheckboxes", $checkbox);

        // Set onclick handlers on toolbar buttons
        $this->jq('#adminer-main-action-add-database')->click($this->rq(Database::class)->add());

        // Set onclick handlers on database names
        $database = jq()->parent()->attr('data-name');
        $this->jq('.' . $dbNameClass . '>a', '#' . $this->package->getDbContentId())
            ->click($this->rq(Database::class)->select($database));

        // Set onclick handlers on database drop
        $database = jq()->parent()->attr('data-name');
        $this->jq('.' . $dbDropClass . '>a', '#' . $this->package->getDbContentId())
            ->click($this->rq(Database::class)->drop($database)
            ->confirm("Delete database {1}?", $database));

        return $this->response;
    }

    /**
     * Show the privileges of a server
     *
     * @after('call' => 'showBreadcrumbs')
     * @after('call' => 'selectMenuItem', 'with' => ['.menu-action-privileges', 'adminer-database-menu'])
     *
     * @return Response
     */
    public function showPrivileges(): Response
    {
        $privilegesInfo = $this->db->getPrivileges();

        $editClass = 'adminer-privilege-name';
        $optionClass = 'jaxon-adminer-grant';
        // Add links, classes and data values to privileges.
        $privilegesInfo['details'] = \array_map(function($detail) use($editClass, $optionClass) {
            // Set the grant select options.
            $detail['grants'] = $this->ui->htmlSelect($detail['grants'], $optionClass);
                // Set the Edit button.
            $detail['edit'] = [
                'label' => '<a href="javascript:void(0)">Edit</a>',
                'props' => [
                    'class' => $editClass,
                    'data-user' => $detail['user'],
                    'data-host' => $detail['host'],
                ],
            ];
            return $detail;
        }, $privilegesInfo['details']);

        // Make privileges info available to views
        $this->view()->shareValues($privilegesInfo);

        // Set main menu buttons
        $content = isset($privilegesInfo['mainActions']) ?
            $this->ui->mainActions($privilegesInfo['mainActions']) : '';
        $this->response->html($this->package->getMainActionsId(), $content);

        $content = $this->ui->mainContent($this->renderMainContent());
        $this->response->html($this->package->getDbContentId(), $content);

        // Set onclick handlers on database names
        $user = jq()->parent()->attr('data-user');
        $host = jq()->parent()->attr('data-host');
        $database = jq()->parent()->parent()->find("option.$optionClass:selected")->val();
        $this->jq('.' . $editClass . '>a', '#' . $this->package->getDbContentId())
            ->click($this->rq(User::class)->edit($user, $host, $database));

        // Set onclick handlers on toolbar buttons
        $this->jq('#adminer-main-action-add-user')->click($this->rq(User::class)->add());

        return $this->response;
    }

    /**
     * Show the processes of a server
     *
     * @after('call' => 'showBreadcrumbs')
     * @after('call' => 'selectMenuItem', 'with' => ['.menu-action-processes', 'adminer-database-menu'])
     *
     * @return Response
     */
    public function showProcesses(): Response
    {
        $processesInfo = $this->db->getProcesses();
        // Make processes info available to views
        $this->view()->shareValues($processesInfo);

        // Set main menu buttons
        $content = isset($processesInfo['mainActions']) ?
            $this->ui->mainActions($processesInfo['mainActions']) : '';
        $this->response->html($this->package->getMainActionsId(), $content);

        $content = $this->ui->mainContent($this->renderMainContent());
        $this->response->html($this->package->getDbContentId(), $content);

        return $this->response;
    }

    /**
     * Show the variables of a server
     *
     * @after('call' => 'showBreadcrumbs')
     * @after('call' => 'selectMenuItem', 'with' => ['.menu-action-variables', 'adminer-database-menu'])
     *
     * @return Response
     */
    public function showVariables(): Response
    {
        $variablesInfo = $this->db->getVariables();
        // Make variables info available to views
        $this->view()->shareValues($variablesInfo);

        // Set main menu buttons
        $content = isset($variablesInfo['mainActions']) ?
            $this->ui->mainActions($variablesInfo['mainActions']) : '';
        $this->response->html($this->package->getMainActionsId(), $content);

        $content = $this->ui->mainContent($this->renderMainContent());
        $this->response->html($this->package->getDbContentId(), $content);

        return $this->response;
    }

    /**
     * Show the status of a server
     *
     * @after('call' => 'showBreadcrumbs')
     * @after('call' => 'selectMenuItem', 'with' => ['.menu-action-status', 'adminer-database-menu'])
     *
     * @return Response
     */
    public function showStatus(): Response
    {
        $statusInfo = $this->db->getStatus();
        // Make status info available to views
        $this->view()->shareValues($statusInfo);

        // Set main menu buttons
        $content = isset($statusInfo['mainActions']) ?
            $this->ui->mainActions($statusInfo['mainActions']) : '';
        $this->response->html($this->package->getMainActionsId(), $content);

        $content = $this->ui->mainContent($this->renderMainContent());
        $this->response->html($this->package->getDbContentId(), $content);

        return $this->response;
    }
}
