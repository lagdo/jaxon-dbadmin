<?php

namespace Lagdo\DbAdmin\Db\Traits;

use Lagdo\DbAdmin\Db\Facades\UserFacade;

/**
 * Facade to user functions
 */
trait UserTrait
{
    use AbstractTrait;

    /**
     * Get the facade to user features
     *
     * @return UserFacade
     */
    protected function userFacade(): UserFacade
    {
        return $this->di()->g(UserFacade::class);
    }

    /**
     * Get the privilege list
     * This feature is available only for MySQL
     *
     * @param string $database  The database name
     *
     * @return array
     */
    public function getPrivileges(string $database = ''): array
    {
        $this->connectToServer();
        $this->breadcrumbs()->clear()->item($this->utils->trans->lang('Privileges'));
        return $this->userFacade()->getPrivileges($database);
    }

    /**
     * Get the privileges for a new user
     *
     * @return array
     */
    public function newUserPrivileges(): array
    {
        $this->connectToServer();
        return $this->userFacade()->newUserPrivileges();
    }

    /**
     * Get the privileges for a new user
     *
     * @param string $user      The user name
     * @param string $host      The host name
     * @param string $database  The database name
     *
     * @return array
     */
    public function getUserPrivileges(string $user, string $host, string $database): array
    {
        $this->connectToServer();
        return $this->userFacade()->getUserPrivileges($user, $host, $database);
    }
}
