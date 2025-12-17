<?php

namespace Lagdo\DbAdmin\Db\Service\Admin;

use Lagdo\DbAdmin\Db\Service\Audit\ConnectionProxy;

use function gmdate;

/**
 * Connection to the audit database
 */
trait UserQueryTrait
{
    /**
     * @var ConnectionProxy
     */
    private ConnectionProxy $proxy;

    /**
     * @var int|null
     */
    private int|null $ownerId = null;

    /**
     * @return string
     */
    abstract protected function user(): string;

    /**
     * @return string
     */
    protected function currentTime(): string
    {
        return gmdate('Y-m-d H:i:s');
    }

    /**
     * @param string $username
     *
     * @return int
     */
    private function readOwnerId(string $username): int
    {
        $query = "SELECT id FROM dbadmin_owners WHERE username=:username LIMIT 1";
        $statement = $this->proxy->executeQuery($query, ['username' => $username]);
        return !$statement || !($row = $statement->fetchAssoc()) ? 0 : (int)$row['id'];
    }

    /**
     * @param string $username
     *
     * @return int
     */
    private function newOwnerId(string $username): int
    {
        // Try to save the user and return his id.
        $query = "INSERT INTO dbadmin_owners(username) VALUES (:username)";
        $statement = $this->proxy->executeQuery($query, ['username' => $username]);
        if ($statement !== false) {
            return $this->readOwnerId($username);
        }

        $this->proxy->logWarning('Unable to save new owner in the query audit database.');
        return 0;
    }

    /**
     * @param bool $canCreate
     *
     * @return int
     */
    private function getOwnerId(bool $canCreate = true): int
    {
        if ($this->ownerId !== null) {
            return $this->ownerId;
        }

        if (!$this->proxy->connected()) {
            return 0;
        }

        if (($this->ownerId = $this->readOwnerId($this->user())) > 0) {
            return $this->ownerId;
        }

        // Try to create a new owner entry for the user.
        return !$canCreate ? 0 : ($this->ownerId = $this->newOwnerId($this->user()));
    }
}
