<?php

namespace Lagdo\DbAdmin\Db\Service\Admin;

use function implode;

/**
 * SQL queries logging and storage.
 */
class QueryFavorite
{
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
     * @param ConnectionProxy $proxy
     * @param array $options
     */
    public function __construct(private ConnectionProxy $proxy, array $options)
    {
        $this->enabled = (bool)($options['enduser']['enabled'] ?? false);
        $this->limit = (int)($options['enduser']['limit'] ?? 15);
    }

    /**
     * @param array $values
     *
     * @return bool
     */
    public function createQuery(array $values): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $values = [
            'title' => $values['title'],
            'query' => $values['query'],
            'driver' => $values['driver'],
            'last_update' => $this->proxy->currentTime(),
            'owner_id' => $this->proxy->getOwnerId(),
        ];
        $sql = "INSERT INTO dbadmin_stored_commands (title,query,driver,last_update,owner_id)
VALUES (:title,:query,:driver,:last_update,:owner_id)";
        $statement = $this->proxy->executeQuery($sql, $values);
        if ($statement !== false) {
            return true;
        }

        $this->proxy->logWarning('Unable to save command in the query audit database.');
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
        if (!$this->enabled) {
            return false;
        }

        $values = [
            'title' => $values['title'],
            'query' => $values['query'],
            'driver' => $values['driver'],
            'last_update' => $this->proxy->currentTime(),
            'owner_id' => $this->proxy->getOwnerId(),
            'query_id' => $queryId,
        ];
        $sql = "UPDATE dbadmin_stored_commands SET title=:title,query=:query,
driver=:driver,last_update=:last_update WHERE id=:query_id AND owner_id=:owner_id";
        $statement = $this->proxy->executeQuery($sql, $values);
        if ($statement !== false) {
            return true;
        }

        $this->proxy->logWarning('Unable to save command in the query audit database.');
        return false;
    }

    /**
     * @param int $queryId
     *
     * @return bool
     */
    public function deleteQuery(int $queryId): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $values = [
            'owner_id' => $this->proxy->getOwnerId(),
            'query_id' => $queryId,
        ];
        $sql = "DELETE FROM dbadmin_stored_commands WHERE id=:query_id AND owner_id=:owner_id";
        $statement = $this->proxy->executeQuery($sql, $values);
        if ($statement !== false) {
            return true;
        }

        $this->proxy->logWarning('Unable to save command in the query audit database.');
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
            'owner_id' => $this->proxy->getOwnerId(),
        ];
        $clauses = ['c.owner_id=:owner_id'];
        if (isset($filters['title'])) {
            $values['title'] = "%{$filters['title']}%";
            $clauses[] = "c.title like :title";
        }
        if (isset($filters['driver'])) {
            $values['driver'] = $filters['driver'];
            $clauses[] = "c.driver=:driver";
        }
        if (isset($filters['from'])) {
            $values['from'] = $filters['from'];
            $clauses[] = "c.last_update>=:from";
        }
        if (isset($filters['to'])) {
            $values['to'] = $filters['to'];
            $clauses[] = "c.last_update<=:to";
        }
        return [$values, 'WHERE ' . implode(' AND ', $clauses)];
    }

    /**
     * @param array $filters
     *
     * @return int
     */
    public function getQueryCount(array $filters): int
    {
        if (!$this->enabled) {
            return 0;
        }

        [$values, $whereClause] = $this->getWhereClause($filters);
        $sql = "SELECT count(*) AS cnt FROM dbadmin_stored_commands c $whereClause";
        $statement = $this->proxy->executeQuery($sql, $values);
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
        if (!$this->enabled) {
            return [];
        }

        [$values, $whereClause] = $this->getWhereClause($filters);
        $offsetClause = $page > 1 ? 'OFFSET ' . ($page - 1) * $this->limit : '';
        // PostgreSQL doesn't allow the use of distinct and order by
        // a field not in the select clause in the same SQL query.
        $sql = "SELECT c.* FROM dbadmin_stored_commands c $whereClause
ORDER BY c.last_update DESC, c.id DESC LIMIT {$this->limit} $offsetClause";
        $statement = $this->proxy->executeQuery($sql, $values);
        if ($statement !== false) {
            $commands = [];
            while (($row = $statement->fetchAssoc())) {
                $commands[] = $row;
            }
            return $commands;
        }

        $this->proxy->logWarning('Unable to read commands from the query audit database.');
        return [];
    }

    /**
     * @param int $queryId
     *
     * @return array|null
     */
    public function getQuery(int $queryId): ?array
    {
        if (!$this->enabled) {
            return null;
        }

        $values = [
            'query_id' => $queryId,
            'owner_id' => $this->proxy->getOwnerId(),
        ];
        $sql = "SELECT c.* FROM dbadmin_stored_commands c WHERE id=:query_id AND owner_id=:owner_id";
        $statement = $this->proxy->executeQuery($sql, $values);
        return !$statement ? null : $statement->fetchAssoc();
    }
}
