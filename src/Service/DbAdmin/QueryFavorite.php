<?php

namespace Lagdo\DbAdmin\Service\DbAdmin;

use Lagdo\DbAdmin\Config\AuthInterface;
use Lagdo\DbAdmin\Driver\DriverInterface;
use Lagdo\Facades\Logger;

use function implode;

/**
 * SQL queries logging and storage.
 */
class QueryFavorite
{
    use ConnectionTrait;

    /**
     * @var bool
     */
    private bool $enabled;

    /**
     * @var int
     */
    private int $limit;

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
     * @param array $values
     *
     * @return bool
     */
    public function createQuery(array $values): bool
    {
        $values = [
            'title' => $values['title'],
            'query' => $values['query'],
            'last_update' => $this->currentTime(),
            'owner_id' => $this->getOwnerId(),
        ];
        $sql = "insert into dbadmin_stored_commands" .
            "(title,query,last_update,owner_id) " .
            "values(:title,:query,:last_update,:owner_id)";
        $statement = $this->executeQuery($sql, $values);
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
        $values = [
            'title' => $values['title'],
            'query' => $values['query'],
            'last_update' => $this->currentTime(),
            'owner_id' => $this->getOwnerId(),
            'query_id' => $queryId,
        ];
        $sql = "update dbadmin_stored_commands set " .
            "title=:title,query=:query,last_update=:last_update " .
            "where id=:query_id and owner_id=:owner_id";
        $statement = $this->executeQuery($sql, $values);
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
        $values = [
            'owner_id' => $this->getOwnerId(),
            'query_id' => $queryId,
        ];
        $sql = "delete from dbadmin_stored_commands where " .
            "id=:query_id and owner_id=:owner_id";
        $statement = $this->executeQuery($sql, $values);
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
     * @return array
     */
    private function getWhereClause(array $filters): array
    {
        $values = [
            'owner_id' => $this->getOwnerId(),
        ];
        $clauses = ['c.owner_id=:owner_id'];
        if (isset($filters['title'])) {
            $values['title'] = "%{$filters['title']}%";
            $clauses[] = "c.title like :title";
        }
        if (isset($filters['from'])) {
            $values['from'] = $filters['from'];
            $clauses[] = "c.last_update>=:from";
        }
        if (isset($filters['to'])) {
            $values['to'] = $filters['to'];
            $clauses[] = "c.last_update<=:to";
        }
        return [$values, 'where ' . implode(' and ', $clauses)];
    }

    /**
     * @param array $filters
     *
     * @return int
     */
    public function getQueryCount(array $filters): int
    {
        [$values, $whereClause] = $this->getWhereClause($filters);
        $sql = "select count(*) as cnt from dbadmin_stored_commands c $whereClause";
        $statement = $this->executeQuery($sql, $values);
        return !$statement || !($row = $statement->fetchAssoc()) ? 0 : $row['cnt'];
    }

    /**
     * @param array $filters
     * @param int $page
     *
     * @return array
     */
    public function getQueries(array $filters, int $page): array
    {
        [$values, $whereClause] = $this->getWhereClause($filters);
        $offsetClause = $page > 1 ? 'offset ' . ($page - 1) * $this->limit : '';
        // PostgreSQL doesn't allow the use of distinct and order by
        // a field not in the select clause in the same SQL query.
        $sql = "select c.* from dbadmin_stored_commands c $whereClause " .
            "order by c.last_update desc,c.id desc limit {$this->limit} $offsetClause";
        $statement = $this->executeQuery($sql, $values);
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
        $values = [
            'query_id' => $queryId,
            'owner_id' => $this->getOwnerId(),
        ];
        $sql = "select c.* from dbadmin_stored_commands c where " .
            "id=:query_id and owner_id=:owner_id";
        $statement = $this->executeQuery($sql, $values);
        return !$statement ? null : $statement->fetchAssoc();
    }
}
