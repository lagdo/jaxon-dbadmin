<?php

namespace Lagdo\DbAdmin\App\Ajax\Db\Server;

use Jaxon\Response\AjaxResponse;
use Lagdo\DbAdmin\App\Ajax\Db\Database;
use Lagdo\DbAdmin\App\Ajax\Menu\Db;
use Lagdo\DbAdmin\App\Ajax\Menu\DbActions;
use Lagdo\DbAdmin\App\Ajax\Menu\DbList;
use Lagdo\DbAdmin\App\Ajax\Menu\SchemaList;
use Lagdo\DbAdmin\App\Ajax\Menu\Server as ServerInfo;
use Lagdo\DbAdmin\App\Ajax\Menu\ServerActions;
use Lagdo\DbAdmin\App\Ajax\Page\Content;
use Lagdo\DbAdmin\App\CallableDbClass;
use Lagdo\DbAdmin\Db\Exception\DbException;

use function array_values;
use function count;

/**
 * @before('call' => 'checkServerAccess')
 */
class Server extends CallableDbClass
{
    /**
     * @var string
     */
    protected $overrides = Content::class;

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
     * @return AjaxResponse
     */
    public function connect(bool $hasServerAccess): AjaxResponse
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

        return $this->cl(Databases::class)->update();
    }
}
