<?php

namespace Lagdo\DbAdmin\Service\DbAdmin;

use Lagdo\DbAdmin\Config\AuthInterface;
use Lagdo\DbAdmin\Driver\DriverInterface;
use Lagdo\DbAdmin\Service\Options;
use Lagdo\Facades\Logger;

/**
 * SQL queries logging and storage.
 */
class QueryHistory
{
    use ConnectionTrait;

    /**
     * @var bool
     */
    private bool $historyEnabled;

    /**
     * @var bool
     */
    private bool $historyDistinct;

    /**
     * @var int
     */
    private int $historyLimit;

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
        $this->historyEnabled = (bool)($options['history']['enabled'] ?? false);
        $this->historyDistinct = (bool)($options['history']['distinct'] ?? false);
        $this->historyLimit = (int)($options['history']['limit'] ?? 15);
        if (!$this->historyEnabled) {
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
     * @return array
     */
    public function getQueries(): array
    {
        if (!$this->historyEnabled) {
            return [];
        }
        $ownerId = $this->getOwnerId(false);
        if ($ownerId === 0) {
            return [];
        }

        // PostgreSQL doesn't allow the use of distinct and order by
        // a field not in the select clause in the same SQL query.
        $category = Options::CAT_EDITOR;
        $select = $this->historyDistinct && $this->driver->jush() !== 'pgsql' ?
            'select distinct' : 'select';
        $query = "$select query from dbadmin_runned_commands c " .
            "where c.owner_id=:owner_id and c.category=:category " .
            "order by c.last_update desc limit {$this->historyLimit}";
        $values = [
            'owner_id' => $ownerId,
            'category' => $category,
        ];
        $statement = $this->executeQuery($query, $values);
        if ($statement !== false) {
            $commands = [];
            $id = 1;
            while (($row = $statement->fetchAssoc())) {
                $commands[$id++] = $row['query'];
            }
            return $commands;
        }

        Logger::warning('Unable to read commands from the query logging database.', [
            'error' => $this->connection->error(),
        ]);
        return [];
    }
}
