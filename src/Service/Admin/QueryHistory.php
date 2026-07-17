<?php

namespace Lagdo\DbAdmin\Support\Service\Admin;

use Lagdo\DbAdmin\Support\Service\Audit\Options;

/**
 * SQL queries logging and storage.
 */
class QueryHistory
{
    /**
     * @var bool
     */
    private bool $showHistory;

    /**
     * @var bool
     */
    private bool $historyDistinct;

    /**
     * @var int
     */
    private int $historyLimit;

    /**
     * @param AuditConnection $audit
     * @param array $options
     */
    public function __construct(private AuditConnection $audit, array $options)
    {
        $this->showHistory = (bool)($options['history']['show'] ?? false);
        $this->historyDistinct = (bool)($options['history']['distinct'] ?? false);
        $this->historyLimit = (int)($options['history']['limit'] ?? 15);
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
        if (!$this->showHistory || ($userId = $this->audit->getUserId(false)) === 0) {
            return [];
        }

        // PostgreSQL doesn't allow the use of distinct and order by
        // a column not in the select clause in the same SQL query.
        $category = Options::CAT_EDITOR;
        $select = $this->historyDistinct && $this->audit->pgsql() ?
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
        $result = $this->audit->executeQuery($query, $values);
        if ($result->hasRowset()) {
            $id = 1;
            $commands = [];
            while (($row = $result->fetchAssoc())) {
                $commands[$id++] = $row;
            }
            return $commands;
        }

        $this->audit->logWarning('Unable to read commands from the query audit database.');
        return [];
    }
}
