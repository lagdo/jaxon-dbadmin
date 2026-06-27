<?php

namespace Lagdo\DbAdmin\Support\Service\Admin;

use function implode;

/**
 * SQL queries logging and storage.
 */
class QueryFavorite
{
    /**
     * @var bool
     */
    private bool $showFavorite;

    /**
     * @var int
     */
    private int $favoriteLimit;

    /**
     * @param ConnectionProxy $proxy
     * @param array $options
     */
    public function __construct(private ConnectionProxy $proxy, array $options)
    {
        $this->showFavorite = (bool)($options['favorite']['show'] ?? false);
        $this->favoriteLimit = (int)($options['favorite']['limit'] ?? 15);
    }

    /**
     * @param array $values
     *
     * @return bool
     */
    public function createQuery(array $values): bool
    {
        if (!$this->showFavorite) {
            return false;
        }

        $values = [
            'title' => $values['title'],
            'query' => $values['query'],
            'driver' => $values['driver'],
            'last_update' => $this->proxy->currentTime(),
            'user_id' => $this->proxy->getUserId(),
        ];
        $sql = "INSERT INTO dbadmin_stored_commands (title,query,driver,last_update,user_id)
VALUES (:title,:query,:driver,:last_update,:user_id)";
        $result = $this->proxy->executeQuery($sql, $values);
        if (!$result->hasError()) {
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
        if (!$this->showFavorite) {
            return false;
        }

        $values = [
            'title' => $values['title'],
            'query' => $values['query'],
            'driver' => $values['driver'],
            'last_update' => $this->proxy->currentTime(),
            'user_id' => $this->proxy->getUserId(),
            'query_id' => $queryId,
        ];
        $sql = "UPDATE dbadmin_stored_commands SET title=:title,query=:query,
driver=:driver,last_update=:last_update WHERE id=:query_id AND user_id=:user_id";
        $result = $this->proxy->executeQuery($sql, $values);
        if (!$result->hasError()) {
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
        if (!$this->showFavorite) {
            return false;
        }

        $values = [
            'user_id' => $this->proxy->getUserId(),
            'query_id' => $queryId,
        ];
        $sql = "DELETE FROM dbadmin_stored_commands WHERE id=:query_id AND user_id=:user_id";
        $result = $this->proxy->executeQuery($sql, $values);
        if (!$result->hasError()) {
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
        return $this->favoriteLimit;
    }

    /**
     * @param array $filters
     *
     * @return array
     */
    private function getWhereClause(array $filters): array
    {
        $values = [
            'user_id' => $this->proxy->getUserId(),
        ];
        $clauses = ['c.user_id=:user_id'];
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
        if (!$this->showFavorite) {
            return 0;
        }

        [$values, $whereClause] = $this->getWhereClause($filters);
        $sql = "SELECT count(*) AS cnt FROM dbadmin_stored_commands c $whereClause";
        $result = $this->proxy->executeQuery($sql, $values);
        return $result->hasRowset() && ($row = $result->fetchAssoc()) ? $row['cnt'] : 0;
    }

    /**
     * @param array $filters
     * @param int $page
     *
     * @return array
     */
    public function getQueries(array $filters, int $page): array
    {
        if (!$this->showFavorite) {
            return [];
        }

        [$values, $whereClause] = $this->getWhereClause($filters);
        $offsetClause = $page > 1 ? 'OFFSET ' . ($page - 1) * $this->favoriteLimit : '';
        // PostgreSQL doesn't allow the use of distinct and order by
        // a column not in the select clause in the same SQL query.
        $sql = "SELECT c.* FROM dbadmin_stored_commands c $whereClause
ORDER BY c.last_update DESC, c.id DESC LIMIT {$this->favoriteLimit} $offsetClause";
        $result = $this->proxy->executeQuery($sql, $values);
        if ($result->hasRowset()) {
            $commands = [];
            while (($row = $result->fetchAssoc())) {
                $commands[$row['id']] = $row;
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
        if (!$this->showFavorite) {
            return null;
        }

        $values = [
            'query_id' => $queryId,
            'user_id' => $this->proxy->getUserId(),
        ];
        $sql = "SELECT c.* FROM dbadmin_stored_commands c WHERE id=:query_id AND user_id=:user_id";
        $result = $this->proxy->executeQuery($sql, $values);
        return $result->hasRowset() ? $result->fetchAssoc() : null;
    }
}
