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
     * The constructor
     *
     * @param ConnectionProxy $proxy
     * @param array $options
     */
    public function __construct(private ConnectionProxy $proxy, array $options)
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
        if (!$this->showHistory || ($userId = $this->proxy->getUserId(false)) === 0) {
            return [];
        }

        // PostgreSQL doesn't allow the use of distinct and order by
        // a field not in the select clause in the same SQL query.
        $category = Options::CAT_EDITOR;
        $select = $this->historyDistinct && $this->proxy->pgsql() ?
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
        $statement = $this->proxy->executeQuery($query, $values);
        if ($statement !== false) {
            $id = 1;
            $commands = [];
            while (($row = $statement->fetchAssoc())) {
                $commands[$id++] = $row;
            }
            return $commands;
        }

        $this->proxy->logWarning('Unable to read commands from the query audit database.');
        return [];
    }
}
