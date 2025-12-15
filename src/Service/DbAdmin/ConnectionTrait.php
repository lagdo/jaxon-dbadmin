<?php

namespace Lagdo\DbAdmin\Db\Service\DbAdmin;

use Lagdo\DbAdmin\Driver\Db\Connection;
use Lagdo\DbAdmin\Driver\Db\StatementInterface;
use Lagdo\DbAdmin\Driver\DriverInterface;
use Lagdo\Facades\Logger;

use function gmdate;

/**
 * Connection to the audit database
 */
trait ConnectionTrait
{
    /**
     * @var int|null
     */
    private int|null $ownerId = null;

    /**
     * @var Connection
     */
    private Connection $connection;

    /**
     * @param DriverInterface $driver
     * @param array $database
     */
    protected function connect(DriverInterface $driver, array $database)
    {
        $this->connection = $driver->createConnection($database);
        $this->connection->open($database['name'], $database['schema'] ?? '');
    }

    /**
     * @var string
     */
    protected function currentTime(): string
    {
        return gmdate('Y-m-d H:i:s');
    }

    /**
     * @var Connection
     */
    protected function connection(): Connection
    {
        return $this->connection;
    }

    /**
     * @param string $query
     * @param array $values
     *
     * @return bool|StatementInterface
     */
    private function executeQuery(string $query, array $values): bool|StatementInterface
    {
        $st = $this->connection->prepareStatement($query);
        return $this->connection->executeStatement($st, $values) ?? false;
    }

    /**
     * @var string
     */
    abstract protected function user(): string;

    /**
     * @param string $username
     *
     * @return int
     */
    private function readOwnerId(string $username): int
    {
        $query = "select id from dbadmin_owners where username=:username limit 1";
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
        $query = "insert into dbadmin_owners(username) values(:username)";
        $statement = $this->executeQuery($query, ['username' => $username]);
        if ($statement !== false) {
            return $this->readOwnerId($username);
        }

        Logger::warning('Unable to save new owner in the query audit database.', [
            'error' => $this->connection?->error() ??
                'Not connected to the audit database',
        ]);
        return false;
    }

    /**
     * @param bool $canCreate
     *
     * @return int
     */
    private function getOwnerId(bool $canCreate = true): int
    {
        // Todo: use match
        return $this->ownerId !== null ? $this->ownerId :
            ($this->readOwnerId($this->user()) ?: ($canCreate ?
                $this->newOwnerId($this->user()) : 0));
    }
}
