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
     * @param AuditDatabase $auditDb
     * @param array $options
     */
    public function __construct(private AuditDatabase $auditDb, array $options)
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
        if (($userId = $this->auditDb->getUserId()) === 0) {
            $this->auditDb->logWarning('Unable to find a valid user for the audit query.');
            return false;
        }

        $values = [
            'title' => $values['title'],
            'query' => $values['query'],
            'driver' => $values['driver'],
            'last_update' => $this->auditDb->currentTime(),
            'user_id' => $userId,
        ];
        $sql = "INSERT INTO dbadmin_stored_commands (title,query,driver,last_update,user_id)
VALUES (:title,:query,:driver,:last_update,:user_id)";
        $result = $this->auditDb->executeQuery($sql, $values);
        if (!$result->hasError()) {
            return true;
        }

        $this->auditDb->logWarning('Unable to save command in the query audit database.');
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
        if (($userId = $this->auditDb->getUserId()) === 0) {
            $this->auditDb->logWarning('Unable to find a valid user for the audit query.');
            return false;
        }

        $values = [
            'title' => $values['title'],
            'query' => $values['query'],
            'driver' => $values['driver'],
            'last_update' => $this->auditDb->currentTime(),
            'user_id' => $userId,
            'query_id' => $queryId,
        ];
        $sql = "UPDATE dbadmin_stored_commands SET title=:title,query=:query,
driver=:driver,last_update=:last_update WHERE id=:query_id AND user_id=:user_id";
        $result = $this->auditDb->executeQuery($sql, $values);
        if (!$result->hasError()) {
            return true;
        }

        $this->auditDb->logWarning('Unable to save command in the query audit database.');
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
        if (($userId = $this->auditDb->getUserId()) === 0) {
            $this->auditDb->logWarning('Unable to find a valid user for the audit query.');
            return false;
        }

        $values = [
            'user_id' => $userId,
            'query_id' => $queryId,
        ];
        $sql = "DELETE FROM dbadmin_stored_commands WHERE id=:query_id AND user_id=:user_id";
        $result = $this->auditDb->executeQuery($sql, $values);
        if (!$result->hasError()) {
            return true;
        }

        $this->auditDb->logWarning('Unable to save command in the query audit database.');
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
     * @param int $userId
     *
     * @return array
     */
    private function getWhereClause(array $filters, int $userId): array
    {
        $values = ['user_id' => $userId];
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
        if (($userId = $this->auditDb->getUserId()) === 0) {
            $this->auditDb->logWarning('Unable to find a valid user for the audit query.');
            return 0;
        }

        [$values, $whereClause] = $this->getWhereClause($filters, $userId);
        $sql = "SELECT count(*) AS cnt FROM dbadmin_stored_commands c $whereClause";
        $result = $this->auditDb->executeQuery($sql, $values);
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
        if (($userId = $this->auditDb->getUserId()) === 0) {
            $this->auditDb->logWarning('Unable to find a valid user for the audit query.');
            return [];
        }

        [$values, $whereClause] = $this->getWhereClause($filters, $userId);
        $offsetClause = $page > 1 ? 'OFFSET ' . ($page - 1) * $this->favoriteLimit : '';
        // PostgreSQL doesn't allow the use of distinct and order by
        // a column not in the select clause in the same SQL query.
        $sql = "SELECT c.* FROM dbadmin_stored_commands c $whereClause
ORDER BY c.last_update DESC, c.id DESC LIMIT {$this->favoriteLimit} $offsetClause";
        $result = $this->auditDb->executeQuery($sql, $values);
        if ($result->hasRowset()) {
            $commands = [];
            while (($row = $result->fetchAssoc())) {
                $commands[$row['id']] = $row;
            }
            return $commands;
        }

        $this->auditDb->logWarning('Unable to read commands from the query audit database.');
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
            'user_id' => $this->auditDb->getUserId(),
        ];
        $sql = "SELECT c.* FROM dbadmin_stored_commands c WHERE id=:query_id AND user_id=:user_id";
        $result = $this->auditDb->executeQuery($sql, $values);
        return $result->hasRowset() ? $result->fetchAssoc() : null;
    }
}
