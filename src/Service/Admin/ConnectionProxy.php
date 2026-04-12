<?php

namespace Lagdo\DbAdmin\Db\Service\Admin;

use Lagdo\DbAdmin\Db\Config\AuthInterface;
use Lagdo\DbAdmin\Db\Service\Audit;
use Lagdo\DbAdmin\Driver\EngineInterface;

use function gmdate;

/**
 * Connection to the audit database
 */
class ConnectionProxy extends Audit\ConnectionProxy
{
    /**
     * @var int|null
     */
    private int|null $userId = null;

    /**
     * The constructor
     *
     * @param AuthInterface $auth
     * @param EngineInterface $engine
     * @param array $database
     */
    public function __construct(private AuthInterface $auth,
        EngineInterface $engine, array $database)
    {
        parent::__construct($engine, $database);
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
    private function readUserId(string $username): int
    {
        $query = "SELECT id FROM dbadmin_users WHERE username=:username LIMIT 1";
        $statement = $this->executeQuery($query, ['username' => $username]);
        return !$statement || !($row = $statement->fetchAssoc()) ? 0 : (int)$row['id'];
    }

    /**
     * @param string $username
     *
     * @return int
     */
    private function newUserId(string $username): int
    {
        // Try to save the user and return his id.
        $query = "INSERT INTO dbadmin_users(username) VALUES (:username)";
        $statement = $this->executeQuery($query, ['username' => $username]);
        if ($statement !== false) {
            return $this->readUserId($username);
        }

        $this->logWarning('Unable to save new user in the query audit database.');
        return 0;
    }

    /**
     * @param bool $canCreate
     *
     * @return int
     */
    public function getUserId(bool $canCreate = true): int
    {
        $user = $this->auth->user();
        if (!$this->connected() || !$user) {
            return 0;
        }

        if ($this->userId !== null || ($this->userId = $this->readUserId($user)) > 0) {
            return $this->userId;
        }

        // Try to create a new user entry for the user.
        return !$canCreate ? 0 : ($this->userId = $this->newUserId($user));
    }
}
