<?php

namespace Lagdo\DbAdmin\Db\Service\Admin;

use Lagdo\DbAdmin\Db\Service\Audit\Options;

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
     * The constructor
     *
     * @param ConnectionProxy $proxy
     * @param array $options
     */
    public function __construct(private ConnectionProxy $proxy, array $options)
    {
        $this->historyEnabled = (bool)($options['history']['enabled'] ?? false);
        $this->historyDistinct = (bool)($options['history']['distinct'] ?? false);
        $this->historyLimit = (int)($options['history']['limit'] ?? 15);
    }

    /**
     * @return array
     */
    public function getQueries(): array
    {
        if (!$this->historyEnabled ||
            ($ownerId = $this->proxy->getOwnerId(false)) === 0) {
            return [];
        }

        // PostgreSQL doesn't allow the use of distinct and order by
        // a field not in the select clause in the same SQL query.
        $category = Options::CAT_EDITOR;
        $select = $this->historyDistinct && $this->proxy->jush() !== 'pgsql' ?
            'SELECT DISTINCT' : 'SELECT';
        $query = "$select driver,query FROM dbadmin_runned_commands c " .
            "WHERE c.owner_id=:owner_id AND c.category=:category " .
            "ORDER BY c.last_update DESC LIMIT {$this->historyLimit}";
        $values = [
            'owner_id' => $ownerId,
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
