<?php

namespace Lagdo\DbAdmin\Support\Service\Audit;

use Lagdo\DbAdmin\Support\Provider\DatabaseConfigProvider;

use function count;
use function implode;

/**
 * SQL queries logging and storage.
 */
class QueryLogger
{
    /**
     * @var int
     */
    private int $limit;

    /**
     * @param AuditDatabase $auditDb
     * @param DatabaseConfigProvider $configProvider
     */
    public function __construct(private AuditDatabase $auditDb,
        DatabaseConfigProvider $configProvider)
    {
        $this->limit = $configProvider->getQueryPaginationLimit();
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
            $clauses[] = "u.username like '%{$filters['username']}%'";
        }
        if (isset($filters['category'])) {
            $clauses[] = "e.category={$filters['category']}";
        }
        if (isset($filters['from'])) {
            $clauses[] = "e.last_update>='{$filters['from']}'";
        }
        if (isset($filters['to'])) {
            $clauses[] = "e.last_update<='{$filters['to']}'";
        }

        return count($clauses) === 0 ? '' : 'WHERE ' .
            implode(' AND ', $clauses);
    }

    /**
     * @param array $filters
     *
     * @return int
     */
    public function getCommandCount(array $filters): int
    {
        $whereClause = $this->getWhereClause($filters);
        $query = "SELECT count(*) AS c FROM dbadmin_executions e
INNER JOIN dbadmin_users u ON e.user_id=u.id $whereClause";
        $result = $this->auditDb->executeQuery($query);
        return $result->hasRowset() && ($row = $result->fetchAssoc()) ? $row['c'] : 0;
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
        $offsetClause = $page > 1 ? 'OFFSET ' . ($page - 1) * $this->limit : '';
        // PostgreSQL doesn't allow the use of distinct and order by
        // a column not in the select clause in the same SQL query.
        $query = "SELECT e.*, u.username FROM dbadmin_executions e
INNER JOIN dbadmin_users u ON e.user_id=u.id $whereClause
ORDER BY e.last_update DESC, e.id DESC LIMIT {$this->limit} $offsetClause";
        $result = $this->auditDb->executeQuery($query);
        if ($result->hasRowset()) {
            $commands = [];
            while (($row = $result->fetchAssoc())) {
                $commands[] = $row;
            }
            return $commands;
        }

        $this->auditDb->logWarning('Unable to read commands from the query audit database.');
        return [];
    }
}
