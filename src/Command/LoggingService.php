<?php

namespace Lagdo\DbAdmin\Command;

use Lagdo\DbAdmin\Config\AuthInterface;
use Lagdo\Facades\Logger;
use Lagdo\DbAdmin\Db\DbFacade;
use Lagdo\DbAdmin\Driver\Db\ConnectionInterface;
use Lagdo\DbAdmin\Driver\DriverInterface;

use function gmdate;
use function json_encode;

/**
 * SQL queries logging and storage.
 */
class LoggingService
{
    /**
     * @var int
     */
    private const CAT_LIBRARY = 1;

    /**
     * @var int
     */
    private const CAT_ENDUSER = 2;

    /**
     * @var int
     */
    private const CAT_HISTORY = 3;

    /**
     * @var bool
     */
    private bool $enduserEnabled;

    /**
     * @var bool
     */
    private bool $historyEnabled;

    /**
     * @var int
     */
    private int $historyLimit;

    /**
     * @var int
     */
    private int $category;

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
     * @param DbFacade $db
     * @param DriverInterface $driver
     * @param array $database
     * @param array $options
     */
    public function __construct(private AuthInterface $auth, private DbFacade $db,
        DriverInterface $driver, array $database, array $options)
    {
        $this->connection = $driver->createConnection($database);
        $this->connection->open($database['name'], $database['schema'] ?? '');
        $this->enduserEnabled = (bool)($options['enduser']['enabled'] ?? false);
        $this->historyEnabled = (bool)($options['history']['enabled'] ?? false);
        $this->historyLimit = (int)($options['history']['limit'] ?? 15);
        $this->category = self::CAT_ENDUSER;
    }

    /**
     * @return void
     */
    public function setCategoryToHistory(): void
    {
        $this->category = self::CAT_HISTORY;
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
        $statement = $this->connection
            ->query("insert into dbadmin_owners(username) values('$username')");
        if ($statement !== false) {
            return $this->readOwnerId($username);
        }

        Logger::warning('Unable to save new owner in the query logging database.', [
            'error' => $this->connection->error(),
        ]);
        return false;
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
    private function enduserDisabled(): bool
    {
        return (!$this->enduserEnabled && !$this->historyEnabled) ||
            !$this->auth->user() || !$this->getOwnerId();
    }

    /**
     * @param int $category
     *
     * @return bool
     */
    private function categoryDisabled(int $category): bool
    {
        return (!$this->enduserEnabled && $category === self::CAT_ENDUSER) ||
            ($category < self::CAT_ENDUSER || $category > self::CAT_HISTORY);
    }

    /**
     * @param string $query
     * @param int $category
     *
     * @return bool
     */
    private function saveRunnedCommand(string $query, int $category): bool
    {
        if ($this->categoryDisabled($category)) {
            return false;
        }

        $options = $this->db->getDatabaseOptions();
        if (isset($options['password'])) {
            $options['password'] = '';
        }
        $driver = $options['driver'];
        $options = json_encode($options) ?? '{}';
        // Duplicates on query are checked on client side, not here.
        $ownerId = $this->getOwnerId();
        $now = gmdate('Y-m-d H:i:s');
        $statement = "insert into dbadmin_runned_commands" .
            "(query,driver,options,category,last_update,owner_id) " .
            "values('$query','$driver','$options',$category,'$now',$ownerId)";
        $statement = $this->connection->query($statement) !== false;
        if ($statement !== false) {
            return true;
        }

        Logger::warning('Unable to save command in the query logging database.', [
            'error' => $this->connection->error(),
        ]);
        return false;
    }

    /**
     * @param string $query
     *
     * @return bool
     */
    public function saveCommand(string $query): bool
    {
        $category = $this->category;
        // Reset to the default category.
        $this->category = self::CAT_ENDUSER;
        return $this->enduserDisabled() ? false :
            $this->saveRunnedCommand($query, $category);
    }

    /**
     * @param int $category
     *
     * @return array
     */
    private function getCommands(int $category): array
    {
        if ($this->enduserDisabled()) {
            return [];
        }

        $ownerId = $this->getOwnerId();
        $statement = "select max(id) as id,query from dbadmin_runned_commands c " .
            "where c.owner_id=$ownerId and c.category=$category " .
            "group by query order by c.last_update desc limit {$this->historyLimit}";
        $statement = $this->connection->query($statement);
        if ($statement !== false) {
            $commands = [];
            while (($row = $statement->fetchAssoc())) {
                $commands[$row['id']] = $row['query'];
            }
            return $commands;
        }

        Logger::warning('Unable to read commands from the query logging database.', [
            'error' => $this->connection->error(),
        ]);
        return [];
    }

    /**
     * @return array
     */
    public function getHistoryCommands(): array
    {
        return $this->getCommands(self::CAT_HISTORY);
    }
}
