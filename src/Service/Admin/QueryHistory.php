<?php

namespace Lagdo\DbAdmin\Support\Service\Admin;

use Lagdo\DbAdmin\Support\Provider\DatabaseConfigProvider;
use Lagdo\DbAdmin\Support\Service\Audit\Options;

/**
 * SQL queries logging and storage.
 */
class QueryHistory
{
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
     * @param AuditDatabase $auditDb
     * @param DatabaseConfigProvider $configProvider
     */
    public function __construct(private AuditDatabase $auditDb, DatabaseConfigProvider $configProvider)
    {
        $this->historyEnabled = $configProvider->queryHistoryEnabled();
        $this->historyDistinct = $configProvider->queryHistoryDistinct();
        $this->historyLimit = $configProvider->queryHistoryLimit();
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->historyLimit;
    }

    /**
     * @param int $page
     *
     * @return array
     */
    public function getQueries(int $page): array
    {
        if (!$this->historyEnabled) {
            return [];
        }
        if (($userId = $this->auditDb->getUserId(false)) === 0) {
            $this->auditDb->logWarning('Unable to find a valid user for the audit query.');
            return [];
        }

        // PostgreSQL doesn't allow the use of distinct and order by
        // a column not in the select clause in the same SQL query.
        $category = Options::CAT_EDITOR;
        $select = $this->historyDistinct && $this->auditDb->pgsql() ?
            'SELECT DISTINCT' : 'SELECT';
        $query = "$select driver,query FROM dbadmin_runned_commands c " .
            "WHERE c.user_id=:user_id AND c.category=:category " .
            "ORDER BY c.last_update DESC LIMIT {$this->historyLimit}";
        if ($page > 1) {
            $query .= ' OFFSET ' . ($page - 1) * $this->historyLimit;
        }
        $values = [
            'user_id' => $userId,
            'category' => $category,
        ];
        $result = $this->auditDb->executeQuery($query, $values);
        if ($result->hasRowset()) {
            $id = 1;
            $commands = [];
            while (($row = $result->fetchAssoc())) {
                $commands[$id++] = $row;
            }
            return $commands;
        }

        $this->auditDb->logWarning('Unable to read commands from the query audit database.');
        return [];
    }
}
