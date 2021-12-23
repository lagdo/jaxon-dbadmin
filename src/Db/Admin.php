<?php

namespace Lagdo\DbAdmin\Db;

use Lagdo\DbAdmin\Driver\DriverInterface;

use Exception;

use function trim;
use function implode;
use function is_numeric;
use function preg_match;
use function preg_replace;
use function strtoupper;
use function uniqid;

class Admin
{
    use Traits\AdminQueryTrait;
    use Traits\UserAdminTrait;

    /**
     * @var Util
     */
    public $util;

    /**
     * @var DriverInterface
     */
    public $driver;

    /**
     * @var Translator
     */
    protected $trans;

    /**
     * The constructor
     *
     * @param Util $util
     * @param DriverInterface $driver
     * @param Translator $trans
     */
    public function __construct(Util $util, DriverInterface $driver, Translator $trans)
    {
        $this->util = $util;
        $this->driver = $driver;
        $this->trans = $trans;
    }

    /**
     * Create SQL condition from parsed query string
     *
     * @param array $where Parsed query string
     * @param array $fields
     *
     * @return string
     */
    public function where(array $where, array $fields = []): string
    {
        $clauses = [];
        $wheres = $where["where"] ?? [];
        foreach ((array) $wheres as $key => $value) {
            $key = $this->util->bracketEscape($key, 1); // 1 - back
            $column = $this->util->escapeKey($key);
            $clauses[] = $column .
                // LIKE because of floats but slow with ints
                ($this->driver->jush() == "sql" && is_numeric($value) && preg_match('~\.~', $value) ? " LIKE " .
                $this->driver->quote($value) : ($this->driver->jush() == "mssql" ? " LIKE " .
                $this->driver->quote(preg_replace('~[_%[]~', '[\0]', $value)) : " = " . // LIKE because of text
                $this->driver->unconvertField($fields[$key], $this->driver->quote($value)))); //! enum and set
            if ($this->driver->jush() == "sql" &&
                preg_match('~char|text~', $fields[$key]->type) && preg_match("~[^ -@]~", $value)) {
                // not just [a-z] to catch non-ASCII characters
                $clauses[] = "$column = " . $this->driver->quote($value) . " COLLATE " . $this->driver->charset() . "_bin";
            }
        }
        $nulls = $where["null"] ?? [];
        foreach ((array) $nulls as $key) {
            $clauses[] = $this->util->escapeKey($key) . " IS NULL";
        }
        return implode(" AND ", $clauses);
    }

    /**
     * Remove current user definer from SQL command
     *
     * @param string $query
     *
     * @return string
     */
    public function removeDefiner(string $query): string
    {
        return preg_replace('~^([A-Z =]+) DEFINER=`' .
            preg_replace('~@(.*)~', '`@`(%|\1)', $this->driver->user()) .
            '`~', '\1', $query); //! proper escaping of user
    }

    /**
     * Find out foreign keys for each column
     *
     * @param string $table
     *
     * @return array
     */
    public function columnForeignKeys(string $table): array
    {
        $keys = [];
        foreach ($this->driver->foreignKeys($table) as $foreignKey) {
            foreach ($foreignKey->source as $val) {
                $keys[$val][] = $foreignKey;
            }
        }
        return $keys;
    }

    /**
     * Drop old object and redirect
     *
     * @param string $drop Drop old object query
     *
     * @return void
     * @throws Exception
     */
    public function drop(string $drop)
    {
        $this->executeQuery($drop);
    }

    /**
     * Create a view
     *
     * @param array $values The view values
     *
     * @return bool
     * @throws Exception
     */
    public function createView(array $values)
    {
        // From view.inc.php
        $name = trim($values['name']);
        $type = $values['materialized'] ? ' MATERIALIZED VIEW ' : ' VIEW ';

        $sql = ($this->driver->jush() === 'mssql' ? 'ALTER' : 'CREATE OR REPLACE') .
            $type . $this->driver->table($name) . " AS\n" . $values['select'];
        return $this->executeQuery($sql);
    }

    /**
     * Update a view
     *
     * @param string $view The view name
     * @param array $values The view values
     *
     * @return string
     * @throws Exception
     */
    public function updateView(string $view, array $values): string
    {
        // From view.inc.php
        $origType = 'VIEW';
        if ($this->driver->jush() === 'pgsql') {
            $status = $this->driver->tableStatus($view);
            $origType = strtoupper($status->engine);
        }

        $name = trim($values['name']);
        $type = $values['materialized'] ? 'MATERIALIZED VIEW' : 'VIEW';
        $tempName = $name . '_adminer_' . uniqid();

        return $this->dropAndCreate("DROP $origType " . $this->driver->table($view),
            "CREATE $type " . $this->driver->table($name) . " AS\n" . $values['select'],
            "DROP $type " . $this->driver->table($name),
            "CREATE $type " . $this->driver->table($tempName) . " AS\n" . $values['select'],
            "DROP $type " . $this->driver->table($tempName), $view, $name);
    }

    /**
     * Drop a view
     *
     * @param string $view The view name
     *
     * @return bool
     * @throws Exception
     */
    public function dropView(string $view): bool
    {
        // From view.inc.php
        $origType = 'VIEW';
        if ($this->driver->jush() == 'pgsql') {
            $status = $this->driver->tableStatus($view);
            $origType = strtoupper($status->engine);
        }

        $sql = "DROP $origType " . $this->driver->table($view);
        return $this->executeQuery($sql);
    }
}
