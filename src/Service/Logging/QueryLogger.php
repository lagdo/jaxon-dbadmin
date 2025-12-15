<?php

namespace Lagdo\DbAdmin\Db\Service\Logging;

use Lagdo\DbAdmin\Db\Driver\DbFacade;
use Lagdo\DbAdmin\Db\Service\Options;
use Lagdo\DbAdmin\Driver\Db\Connection;
use Lagdo\DbAdmin\Driver\DriverInterface;
use Lagdo\Facades\Logger;

use function count;
use function implode;

/**
 * SQL queries logging and storage.
 */
class QueryLogger
{
    /**
     * @var Connection
     */
    private Connection $connection;

    /**
     * @var int
     */
    private int $limit;

    /**
     * The constructor
     *
     * @param DbFacade $db
     * @param DriverInterface $driver
     * @param array $database
     * @param array $options
     */
    public function __construct(private DbFacade $db,
        private DriverInterface $driver, array $database, array $options)
    {
        $this->limit = $options['display']['limit'] ?? 15;

        // Connect to the logging database.
        $this->connection = $driver->createConnection($database);
        $this->connection->open($database['name'], $database['schema'] ?? '');
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @return string[]
     */
    public function getCategories(): array
    {
        return [
            Options::CAT_BUILDER => 'Query builder',
            Options::CAT_EDITOR => 'Query editor',
        ];
    }

    /**
     * @param array $filters
     *
     * @return string
     */
    private function getWhereClause(array $filters): string
    {
        $clauses = [];
        if (isset($filters['username'])) {
            $clauses[] = "o.username like '%{$filters['username']}%'";
        }
        if (isset($filters['category'])) {
            $clauses[] = "c.category={$filters['category']}";
        }
        if (isset($filters['from'])) {
            $clauses[] = "c.last_update>='{$filters['from']}'";
        }
        if (isset($filters['to'])) {
            $clauses[] = "c.last_update<='{$filters['to']}'";
        }
        return count($clauses) === 0 ? '' : 'where ' .
            implode(' and ', $clauses);
    }

    /**
     * @param array $filters
     *
     * @return int
     */
    public function getCommandCount(array $filters): int
    {
        $whereClause = $this->getWhereClause($filters);
        $statement = "select count(*) as c from dbadmin_runned_commands c " .
            "inner join dbadmin_owners o on c.owner_id=o.id $whereClause";
        $statement = $this->connection->query($statement);
        return !$statement || !($row = $statement->fetchAssoc()) ? 0 : $row['c'];
    }

    /**
     * @param array $filters
     * @param int $page
     *
     * @return array
     */
    public function getCommands(array $filters, int $page): array
    {
        $whereClause = $this->getWhereClause($filters);
        $offsetClause = $page > 1 ? 'offset ' . ($page - 1) * $this->limit : '';
        // PostgreSQL doesn't allow the use of distinct and order by
        // a field not in the select clause in the same SQL query.
        $statement = "select c.*, o.username from dbadmin_runned_commands c " .
            "inner join dbadmin_owners o on c.owner_id=o.id $whereClause " .
            "order by c.last_update desc,c.id desc limit {$this->limit} $offsetClause";
        $statement = $this->connection->query($statement);
        if ($statement !== false) {
            $commands = [];
            while (($row = $statement->fetchAssoc())) {
                $commands[] = $row;
            }
            return $commands;
        }

        Logger::warning('Unable to read commands from the query logging database.', [
            'error' => $this->connection->error(),
        ]);
        return [];
    }
}
