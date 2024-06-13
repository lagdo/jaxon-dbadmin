<?php

namespace Lagdo\DbAdmin\App\Ajax\Db;

use Jaxon\Response\Response;
use Lagdo\DbAdmin\App\CallableDbClass;
use Lagdo\DbAdmin\App\Ajax\Menu\Db;
use Lagdo\DbAdmin\App\Ajax\Menu\DbActions;
use Lagdo\DbAdmin\App\Ajax\Menu\DbList;
use Lagdo\DbAdmin\App\Ajax\Menu\SchemaList;
use Lagdo\DbAdmin\App\Ajax\Menu\Server as ServerInfo;
use Lagdo\DbAdmin\App\Ajax\Menu\ServerActions;
use Lagdo\DbAdmin\App\Ajax\Page\Content;
use Lagdo\DbAdmin\App\Ajax\Page\PageActions;
use Lagdo\DbAdmin\Db\Exception\DbException;

use function array_values;
use function count;
use function Jaxon\jq;

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
        $this->cl(DbList::class)->update($databasesInfo['databases']);

        // Clear schema list
        $this->cl(SchemaList::class)->clear();

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

        $this->cl(ServerInfo::class)->update($serverInfo['server'], $serverInfo['user']);

        // Show the server
        $this->cl(ServerActions::class)->update($serverInfo['sqlActions']);
        $this->cl(DbActions::class)->clear();

        $this->cl(Db::class)->showServer($serverInfo['menuActions']);

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
        $actions = [
            [$this->trans->lang('Create database'), $this->rq(Database::class)->add()],
        ];
        $this->cl(PageActions::class)->update($actions);

        // Add checkboxes to database table
        $checkbox = 'database';
        $content = $this->ui->mainContent($this->renderMainContent(['checkbox' => $checkbox]), $checkbox);
        $this->cl(Content::class)->showHtml($content);

        // Set onclick handlers on table checkbox
        $this->response->call("jaxon.dbadmin.selectTableCheckboxes", $checkbox);

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
        $this->cl(PageActions::class)->update($privilegesInfo['mainActions'] ?? []);

        $content = $this->ui->mainContent($this->renderMainContent());
        $this->cl(Content::class)->showHtml($content);

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
        $this->cl(PageActions::class)->update($processesInfo['mainActions'] ?? []);

        $content = $this->ui->mainContent($this->renderMainContent());
        $this->cl(Content::class)->showHtml($content);

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
        $this->cl(PageActions::class)->update($variablesInfo['mainActions'] ?? []);

        $content = $this->ui->mainContent($this->renderMainContent());
        $this->cl(Content::class)->showHtml($content);

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
        $this->cl(PageActions::class)->update($statusInfo['mainActions'] ?? []);

        $content = $this->ui->mainContent($this->renderMainContent());
        $this->cl(Content::class)->showHtml($content);

        return $this->response;
    }
}
