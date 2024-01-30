<?php

namespace Lagdo\DbAdmin\Db\User;

use Lagdo\DbAdmin\Db\AbstractFacade;
use Exception;

/**
 * Facade to user functions
 */
trait UserTrait
{
    /**
     * The proxy
     *
     * @var UserFacade
     */
    protected $userFacade = null;

    /**
     * @return AbstractFacade
     */
    abstract public function facade(): AbstractFacade;

    /**
     * Connect to a database server
     *
     * @param string $server    The selected server
     * @param string $database  The database name
     * @param string $schema    The database schema
     *
     * @return void
     */
    abstract public function connect(string $server, string $database = '', string $schema = '');

    /**
     * Set the breadcrumbs items
     *
     * @param array $breadcrumbs
     *
     * @return void
     */
    abstract protected function setBreadcrumbs(array $breadcrumbs);

    /**
     * Get the proxy to user features
     *
     * @return UserFacade
     */
    protected function user(): UserFacade
    {
        if (!$this->userFacade) {
            $this->userFacade = new UserFacade();
            $this->userFacade->init($this->facade());
        }
        return $this->userFacade;
    }

    /**
     * Get the privilege list
     * This feature is available only for MySQL
     *
     * @param string $server    The selected server
     * @param string $database  The database name
     *
     * @return array
     */
    public function getPrivileges(string $server, string $database = ''): array
    {
        $this->connect($server);

        $package = $this->facade()->package;
        $this->setBreadcrumbs([$package->getServerName($server), $this->trans->lang('Privileges')]);

        return $this->user()->getPrivileges($database);
    }

    /**
     * Get the privileges for a new user
     *
     * @param string $server    The selected server
     *
     * @return array
     */
    public function newUserPrivileges(string $server): array
    {
        $this->connect($server);
        return $this->user()->newUserPrivileges();
    }

    /**
     * Get the privileges for a new user
     *
     * @param string $server    The selected server
     * @param string $user      The user name
     * @param string $host      The host name
     * @param string $database  The database name
     *
     * @return array
     */
    public function getUserPrivileges(string $server, string $user, string $host, string $database): array
    {
        $this->connect($server);
        return $this->user()->getUserPrivileges($user, $host, $database);
    }
}
