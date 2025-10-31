<?php

namespace Lagdo\DbAdmin\Service\DbAdmin;

use Lagdo\DbAdmin\Driver\Db\ConnectionInterface;
use Lagdo\DbAdmin\Driver\Db\StatementInterface;
use Lagdo\DbAdmin\Driver\DriverInterface;
use Lagdo\Facades\Logger;

use function gmdate;

/**
 * Connection to the logging database
 */
trait ConnectionTrait
{
    /**
     * @var int|null
     */
    private int|null $ownerId = null;

    /**
     * @var ConnectionInterface
     */
    private ConnectionInterface $connection;

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
     * @var ConnectionInterface
     */
    protected function connection(): ConnectionInterface
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

        Logger::warning('Unable to save new owner in the query logging database.', [
            'error' => $this->connection?->error() ??
                'Not connected to the logging database',
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
