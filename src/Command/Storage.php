<?php

namespace Lagdo\DbAdmin\Command;

use Lagdo\DbAdmin\Config\AuthInterface;
use Lagdo\Facades\Logger;
use Lagdo\DbAdmin\Driver\Db\ConnectionInterface;
use Lagdo\DbAdmin\Driver\DriverInterface;

use function gmdate;

/**
 * SQL queries history and storage.
 */
class Storage
{
    /**
     * @var int
     */
    private const CAT_HISTORY = 0;

    /**
     * @var int
     */
    private const CAT_AUDIT = 1;

    /**
     * @var int
     */
    private const CAT_USER = 2;

    /**
     * @var bool
     */
    private bool $historyEnabled;

    /**
     * @var int
     */
    private int $historyLimit;

    /**
     * @var int|null
     */
    private int|null $ownerId = null;

    /**
     * @var ConnectionInterface
     */
    private ConnectionInterface $connection;

    /**
     * The constructor
     *
     * @param AuthInterface $auth
     * @param DriverInterface $driver
     * @param array $database
     * @param array $options
     */
    public function __construct(private AuthInterface $auth,
        DriverInterface $driver, array $database, array $options)
    {
        $this->connection = $driver->createConnection($database);
        $this->connection->open($database['name'], $database['schema'] ?? '');
        $this->historyEnabled = $options['history']['enabled'] ?? false;
        $this->historyLimit = $options['history']['limit'] ?? 15;
    }

    /**
     * @param string $username
     *
     * @return int
     */
    private function readOwnerId(string $username): int
    {
        $statement = "select id from dbadmin_owners where username='$username' limit 1";
        $ownerId = $this->connection->result($statement);
        return $this->ownerId = !$ownerId ? 0 : (int)$ownerId;
    }

    /**
     * @param string $username
     *
     * @return int
     */
    private function newOwnerId(string $username): int
    {
        // Try to save the user and return his id.
        $statement = $this->connection->query("insert into dbadmin_owners(username) values('$username')");
        if ($statement === false) {
            Logger::warning('Unable to save new owner in the history database.', [
                'error' => $this->connection->error(),
            ]);
            return false;
        }
        return $this->readOwnerId($username);
    }

    /**
     * @return int
     */
    private function getOwnerId(): int
    {
        return $this->ownerId !== null ? $this->ownerId :
            ($this->readOwnerId($this->auth->user()) ?:
                $this->newOwnerId($this->auth->user()));
    }

    /**
     * @return bool
     */
    public function historyDisabled(): bool
    {
        return !$this->historyEnabled || !$this->auth->user() || !$this->getOwnerId();
    }

    /**
     * @param int $category
     *
     * @return array
     */
    private function getCommands(int $category): array
    {
        if ($this->historyDisabled()) {
            return [];
        }

        $ownerId = $this->getOwnerId();
        $statement = "select id,query from dbadmin_commands c " .
            "where c.owner_id=$ownerId and c.category=$category " .
            "order by c.updated_at desc limit {$this->historyLimit}";
        $statement = $this->connection->query($statement);
        if ($statement === false) {
            Logger::warning('Unable to read commands from the history database.', [
                'error' => $this->connection->error(),
            ]);
            return [];
        }

        $commands = [];
        while (($row = $statement->fetchAssoc())) {
            $commands[$row['id']] = $row['query'];
        }
        return $commands;
    }

    /**
     * @return array
     */
    public function getHistoryCommands(): array
    {
        return $this->getCommands(self::CAT_HISTORY);
    }

    /**
     * @return array
     */
    public function getUserCommands(): array
    {
        return $this->getCommands(self::CAT_USER);
    }

    /**
     * @param int $category
     * @param string $query
     *
     * @return bool
     */
    private function saveCommand(int $category, string $query): bool
    {
        if ($this->historyDisabled()) {
            return false;
        }

        $ownerId = $this->getOwnerId();
        // Duplicates on query are checked on client side, not here.
        $now = gmdate('Y-m-d H:i:s');
        $statement = "insert into dbadmin_commands(query,category,updated_at,owner_id) " .
            "values('$query', $category, '$now', $ownerId)";
        $statement = $this->connection->query($statement) !== false;
        if ($statement === false) {
            Logger::warning('Unable to save command in the history database.', [
                'error' => $this->connection->error(),
            ]);
            return false;
        }
        return true;
    }

    /**
     * @param string $query
     *
     * @return bool
     */
    public function saveCommandInHistory(string $query): bool
    {
        return $this->saveCommand(self::CAT_HISTORY, $query);
    }
}
