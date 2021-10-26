<?php

namespace Lagdo\DbAdmin\Db;

use Lagdo\DbAdmin\Driver\UtilInterface;
use Lagdo\DbAdmin\Driver\DriverInterface;

use function trim;
use function substr;
use function strlen;
use function implode;
use function is_numeric;
use function preg_match;
use function preg_replace;
use function microtime;
use function strtoupper;
use function uniqid;

class Admin
{
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
    public function where(array $where, array $fields = [])
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
                $clauses[] = "$column = " . $this->driver->quote($value) . " COLLATE " . $this->driver−>charset() . "_bin";
            }
        }
        $nulls = $where["null"] ?? [];
        foreach ((array) $nulls as $key) {
            $clauses[] = $this->util->escapeKey($key) . " IS NULL";
        }
        return implode(" AND ", $clauses);
    }

    /**
     * Query printed after execution in the message
     *
     * @param string $query Executed query
     * @param string $time Elapsed time
     *
     * @return string
     */
    private function messageQuery(string $query, string $time)
    {
        if (strlen($query) > 1e6) {
            // [\x80-\xFF] - valid UTF-8, \n - can end by one-line comment
            $query = preg_replace('~[\x80-\xFF]+$~', '', substr($query, 0, 1e6)) . "\n…";
        }
        return $query;
    }

    /**
     * Execute query
     *
     * @param string $query
     * @param bool $execute
     * @param bool $failed
     * @param string $time
     *
     * @return bool
     */
    private function executeQuery(string $query, bool $execute = true, bool $failed = false, string $time = '')
    {
        if ($execute) {
            $start = microtime(true);
            $failed = !$this->driver->query($query);
            $time = $this->trans->formatTime($start);
        }
        if ($failed) {
            $sql = '';
            if ($query) {
                $sql = $this->messageQuery($query, $time);
            }
            throw new DriverException($this->error() . $sql);
        }
        return true;
    }

    /**
     * Execute remembered queries
     *
     * @param bool $failed
     *
     * @return bool
     */
    private function executeSavedQuery(bool $failed)
    {
        list($queries, $time) = $this->driver->queries();
        return $this->executeQuery($queries, false, $failed, $time);
    }

    /**
     * Drop old object and create a new one
     *
     * @param string $drop          Drop old object query
     * @param string $create        Create new object query
     * @param string $dropCreated   Drop new object query
     * @param string $test          Create test object query
     * @param string $dropTest      Drop test object query
     * @param string $oldName
     * @param string $newName
     *
     * @return string
     */
    protected function dropAndCreate(string $drop, string $create, string $dropCreated,
        string $test, string $dropTest, string $oldName, string $newName)
    {
        if ($oldName == '' && $newName == '') {
            $this->executeQuery($drop);
            return 'dropped';
        }
        if ($oldName == '') {
            $this->executeQuery($create);
            return 'created';
        }
        if ($oldName != $newName) {
            $created = $this->driver->queries($create);
            $dropped = $this->driver->queries($drop);
            // $this->executeSavedQuery(!($created && $this->driver->queries($drop)));
            if (!$dropped && $created) {
                $this->driver->queries($dropCreated);
            }
            return 'altered';
        }
        $this->executeSavedQuery(!($this->driver->queries($test) &&
            $this->driver->queries($dropTest) &&
            $this->driver->queries($drop) && $this->driver->queries($create)));
        return 'altered';
    }

    /**
     * Drop old object and redirect
     *
     * @param string $drop          Drop old object query
     *
     * @return void
     */
    public function drop(string $drop)
    {
        $this->executeQuery($drop);
    }

    /**
     * Create a view
     *
     * @param array  $values    The view values
     *
     * @return array
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
     * @param string $view      The view name
     * @param array  $values    The view values
     *
     * @return string
     */
    public function updateView(string $view, array $values)
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
     * @param string $view      The view name
     *
     * @return array
     */
    public function dropView(string $view)
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
