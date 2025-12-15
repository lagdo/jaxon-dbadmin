<?php

namespace Lagdo\DbAdmin\Db\Service\DbAdmin;

use Lagdo\DbAdmin\Db\Config\AuthInterface;
use Lagdo\DbAdmin\Driver\Db\Connection;
use Lagdo\DbAdmin\Driver\DriverInterface;
use Lagdo\DbAdmin\Db\Service\Options;
use Lagdo\Facades\Logger;

use function json_encode;

/**
 * SQL queries logging and storage.
 */
class QueryLogger
{
    use ConnectionTrait;

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
     * @var Connection
     */
    private Connection $connection;

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
        $this->connect($driver, $database);
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
        $values = [
            'query' => $query,
            'driver' => $this->userDatabase['driver'],
            'options' => json_encode($this->userDatabase) ?? '{}',
            'category' => $category,
            'last_update' => $this->currentTime(),
            'owner_id' => $this->getOwnerId(),
        ];
        // Duplicates on query are checked on client side, not here.
        $query = "insert into dbadmin_runned_commands" .
            "(query,driver,options,category,last_update,owner_id) values" .
            "(:query,:driver,:options,:category,:last_update,:owner_id)";
        $statement = $this->executeQuery($query, $values);
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
