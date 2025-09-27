<?php

namespace Lagdo\DbAdmin\Service\DbAdmin;

use Lagdo\DbAdmin\Config\AuthInterface;
use Lagdo\DbAdmin\Driver\Db\ConnectionInterface;
use Lagdo\DbAdmin\Driver\DriverInterface;
use Lagdo\Facades\Logger;

use function count;
use function implode;
use function gmdate;

/**
 * SQL queries logging and storage.
 */
class QueryFavorite
{
    use OwnerTrait;

    /**
     * @var bool
     */
    private bool $enabled;

    /**
     * @var int
     */
    private int $limit;

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
        $this->enabled = (bool)($options['enduser']['enabled'] ?? false);
        $this->limit = (int)($options['enduser']['limit'] ?? 15);
        if (!$this->enabled) {
            return;
        }

        // Connect to the logging database.
        $this->connection = $driver->createConnection($database);
        $this->connection->open($database['name'], $database['schema'] ?? '');
    }

    /**
     * @var ConnectionInterface
     */
    protected function connection(): ConnectionInterface
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
     * @param array $values
     *
     * @return bool
     */
    public function createQuery(array $values): bool
    {
        $title = $values['title'];
        $query = $values['query'];
        $ownerId = $this->getOwnerId();
        $now = gmdate('Y-m-d H:i:s');
        $statement = "insert into dbadmin_stored_commands" .
            "(title,query,last_update,owner_id) " .
            "values('$title','$query','$now',$ownerId)";
        $statement = $this->connection->query($statement);
        if ($statement !== false) {
            return true;
        }

        Logger::warning('Unable to save command in the query logging database.', [
            'error' => $this->connection->error(),
        ]);
        return false;
    }

    /**
     * @param int $queryId
     * @param array $values
     *
     * @return bool
     */
    public function updateQuery(int $queryId, array $values): bool
    {
        $title = $values['title'];
        $query = $values['query'];
        $ownerId = $this->getOwnerId();
        $now = gmdate('Y-m-d H:i:s');
        $statement = "update dbadmin_stored_commands set " .
            "title='$title',query='$query',last_update='$now' " .
            "where id=$queryId and owner_id=$ownerId";
        $statement = $this->connection->query($statement);
        if ($statement !== false) {
            return true;
        }

        Logger::warning('Unable to save command in the query logging database.', [
            'error' => $this->connection->error(),
        ]);
        return false;
    }

    /**
     * @param int $queryId
     *
     * @return bool
     */
    public function deleteQuery(int $queryId): bool
    {
        $ownerId = $this->getOwnerId();
        $statement = "delete from dbadmin_stored_commands where " .
            "id=$queryId and owner_id=$ownerId";
        $statement = $this->connection->query($statement);
        if ($statement !== false) {
            return true;
        }

        Logger::warning('Unable to save command in the query logging database.', [
            'error' => $this->connection->error(),
        ]);
        return false;
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @param array $filters
     *
     * @return string
     */
    private function getWhereClause(array $filters): string
    {
        $clauses = [];
        if (isset($filters['title'])) {
            $clauses[] = "c.title like '%{$filters['title']}%'";
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
    public function getQueryCount(array $filters): int
    {
        $whereClause = $this->getWhereClause($filters);
        $statement = "select count(*) as c from dbadmin_stored_commands c " .
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
    public function getQueries(array $filters, int $page): array
    {
        $whereClause = $this->getWhereClause($filters);
        $offsetClause = $page > 1 ? 'offset ' . ($page - 1) * $this->limit : '';
        // PostgreSQL doesn't allow the use of distinct and order by
        // a field not in the select clause in the same SQL query.
        $statement = "select c.* from dbadmin_stored_commands c $whereClause " .
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

    /**
     * @param int $queryId
     *
     * @return array|null
     */
    public function getQuery(int $queryId): ?array
    {
        $ownerId = $this->getOwnerId();
        $statement = "select c.* from dbadmin_stored_commands c where " .
            "id=$queryId and owner_id=$ownerId";
        $statement = $this->connection->query($statement);
        return !$statement ? null : $statement->fetchAssoc();
    }
}
