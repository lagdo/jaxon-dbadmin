<?php

namespace Lagdo\DbAdmin\Db\Service\Audit;

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
     * The constructor
     *
     * @param ConnectionProxy $proxy
     * @param array $options
     */
    public function __construct(private ConnectionProxy $proxy, array $options)
    {
        $this->limit = $options['display']['limit'] ?? 15;
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
        $statement = "SELECT count(*) AS c FROM dbadmin_runned_commands c
INNER JOIN dbadmin_owners o ON c.owner_id=o.id $whereClause";
        $statement = $this->proxy->executeQuery($statement);
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
        $offsetClause = $page > 1 ? 'OFFSET ' . ($page - 1) * $this->limit : '';
        // PostgreSQL doesn't allow the use of distinct and order by
        // a field not in the select clause in the same SQL query.
        $statement = "SELECT c.*, o.username FROM dbadmin_runned_commands c
INNER JOIN dbadmin_owners o ON c.owner_id=o.id $whereClause
ORDER BY c.last_update DESC, c.id DESC LIMIT {$this->limit} $offsetClause";
        $statement = $this->proxy->executeQuery($statement);
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
}
