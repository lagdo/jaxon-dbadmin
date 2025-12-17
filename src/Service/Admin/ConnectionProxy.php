<?php

namespace Lagdo\DbAdmin\Db\Service\Admin;

use Lagdo\DbAdmin\Db\Config\AuthInterface;
use Lagdo\DbAdmin\Db\Service\Audit;
use Lagdo\DbAdmin\Driver\DriverInterface;

use function gmdate;

/**
 * Connection to the audit database
 */
class ConnectionProxy extends Audit\ConnectionProxy
{
    /**
     * @var int|null
     */
    private int|null $ownerId = null;

    /**
     * The constructor
     *
     * @param AuthInterface $auth
     * @param DriverInterface $driver
     * @param array $database
     */
    public function __construct(private AuthInterface $auth,
        DriverInterface $driver, array $database)
    {
        parent::__construct($driver, $database);
    }

    /**
     * @return string
     */
    public function currentTime(): string
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
        $statement = $this->executeQuery($query, ['username' => $username]);
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
        $statement = $this->executeQuery($query, ['username' => $username]);
        if ($statement !== false) {
            return $this->readOwnerId($username);
        }

        $this->logWarning('Unable to save new owner in the query audit database.');
        return 0;
    }

    /**
     * @param bool $canCreate
     *
     * @return int
     */
    public function getOwnerId(bool $canCreate = true): int
    {
        $user = $this->auth->user();
        if (!$this->connected() || !$user) {
            return 0;
        }

        if ($this->ownerId !== null || ($this->ownerId = $this->readOwnerId($user)) > 0) {
            return $this->ownerId;
        }

        // Try to create a new owner entry for the user.
        return !$canCreate ? 0 : ($this->ownerId = $this->newOwnerId($user));
    }
}
