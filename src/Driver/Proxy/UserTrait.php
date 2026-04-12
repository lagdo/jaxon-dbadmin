<?php

namespace Lagdo\DbAdmin\Db\Driver\Proxy;

/**
 * Proxy to user functions
 */
trait UserTrait
{
    use AbstractProxyTrait;

    /**
     * Get the proxy to user features
     *
     * @return UserProxy
     */
    protected function userProxy(): UserProxy
    {
        return $this->di()->g(UserProxy::class);
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
        $this->breadcrumbs()->clear()->item($this->utils()->lang('Privileges'));
        return $this->userProxy()->getPrivileges($database);
    }

    /**
     * Get the privileges for a new user
     *
     * @return array
     */
    public function newUserPrivileges(): array
    {
        $this->connectToServer();
        return $this->userProxy()->newUserPrivileges();
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
        return $this->userProxy()->getUserPrivileges($user, $host, $database);
    }
}
