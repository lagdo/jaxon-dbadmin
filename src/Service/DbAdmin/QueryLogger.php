<?php

namespace Lagdo\DbAdmin\Service\DbAdmin;

use Lagdo\DbAdmin\Config\AuthInterface;
use Lagdo\DbAdmin\Driver\Db\ConnectionInterface;
use Lagdo\DbAdmin\Driver\DriverInterface;
use Lagdo\DbAdmin\Service\Options;
use Lagdo\Facades\Logger;

use function gmdate;
use function json_encode;

/**
 * SQL queries logging and storage.
 */
class QueryLogger
{
    use OwnerTrait;

    /**
     * @var bool
     */
    private bool $enduserEnabled;

    /**
     * @var bool
     */
    private bool $historyEnabled;

    /**
     * @var array
     */
    private array $userDatabase;

    /**
     * @var int
     */
    private int $category;

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
        private DriverInterface $driver, array $database, array $options)
    {
        $this->enduserEnabled = (bool)($options['enduser']['enabled'] ?? false);
        $this->historyEnabled = (bool)($options['history']['enabled'] ?? false);
        $this->category = Options::CAT_BUILDER;
        $this->userDatabase = $options['database'];
        if (!$this->enduserEnabled && !$this->historyEnabled) {
            return;
        }

        // Connect to the logging database.
        $this->connection = $driver->createConnection($database);
        $this->connection->open($database['name'], $database['schema'] ?? '');
    }

    /**
     * @var ConnectionInterface|null
     */
    protected function connection(): ?ConnectionInterface
    {
        return $this->connection;
    }

    /**
     * @var string
     */
    protected function user(): string
    {
        return $this->auth->user();
    }

    /**
     * @return void
     */
    public function setCategoryToHistory(): void
    {
        $this->category = Options::CAT_EDITOR;
    }

    /**
     * @return bool
     */
    private function enduserDisabled(): bool
    {
        return (!$this->enduserEnabled && !$this->historyEnabled) ||
            !$this->auth->user() || !$this->getOwnerId(true);
    }

    /**
     * @param int $category
     *
     * @return bool
     */
    private function categoryDisabled(int $category): bool
    {
        return (!$this->enduserEnabled && $category === Options::CAT_BUILDER) ||
            ($category < Options::CAT_BUILDER || $category > Options::CAT_EDITOR);
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

        if (isset($this->userDatabase['password'])) {
            // Hide the password.
            $this->userDatabase['password'] = '';
        }
        $driver = $this->userDatabase['driver'];
        $options = json_encode($this->userDatabase) ?? '{}';
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
        $this->category = Options::CAT_BUILDER;
        return $this->enduserDisabled() ? false :
            $this->saveRunnedCommand($query, $category);
    }
}
