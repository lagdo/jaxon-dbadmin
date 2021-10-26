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
     * Get the users and hosts
     *
     * @return array
     */
    public function getUsers()
    {
        // From privileges.inc.php
        $statement = $this->driver->query("SELECT User, Host FROM mysql." .
            ($database == "" ? "user" : "db WHERE " . $this->driver->quote($database) . " LIKE Db") .
            " ORDER BY Host, User");
        // $grant = $statement;
        if (!$statement) {
            // list logged user, information_schema.USER_PRIVILEGES lists just the current user too
            $statement = $this->driver->query("SELECT SUBSTRING_INDEX(CURRENT_USER, '@', 1) " .
                "AS User, SUBSTRING_INDEX(CURRENT_USER, '@', -1) AS Host");
        }
        $users = [];
        while ($row = $statement->fetchAssoc()) {
            $users[] = $row;
        }
        return $users;
    }

    /**
     * Get the grants of a user on a given host
     *
     * @param string $user      The user name
     * @param string $host      The host name
     * @param string $password  The user password
     *
     * @return array
     */
    public function getUserGrants(string $user, string $host, string &$password)
    {
        // From user.inc.php
        $grants = [];

        //! use information_schema for MySQL 5 - column names in column privileges are not escaped
        if (($statement = $this->driver->query("SHOW GRANTS FOR " .
            $this->driver->quote($user) . "@" . $this->driver->quote($host)))) {
            while ($row = $statement->fetchRow()) {
                if (\preg_match('~GRANT (.*) ON (.*) TO ~', $row[0], $match) &&
                    \preg_match_all('~ *([^(,]*[^ ,(])( *\([^)]+\))?~', $match[1], $matches, PREG_SET_ORDER)) { //! escape the part between ON and TO
                    foreach ($matches as $val) {
                        $match2 = $match[2] ?? '';
                        $val2 = $val[2] ?? '';
                        if ($val[1] != "USAGE") {
                            $grants["$match2$val2"][$val[1]] = true;
                        }
                        if (\preg_match('~ WITH GRANT OPTION~', $row[0])) { //! don't check inside strings and identifiers
                            $grants["$match2$val2"]["GRANT OPTION"] = true;
                        }
                    }
                }
                if (\preg_match("~ IDENTIFIED BY PASSWORD '([^']+)~", $row[0], $match)) {
                    $password  = $match[1];
                }
            }
        }

        return $grants;
    }

    /**
     * Get the user privileges
     *
     * @param array $grants     The user grants
     *
     * @return array
     */
    public function getUserPrivileges(array $grants)
    {
        $features = $this->driver->privileges();
        $privileges = [];
        $contexts = [
            "" => "",
            "Server Admin" => $this->trans->lang('Server'),
            "Databases" => $this->trans->lang('Database'),
            "Tables" => $this->trans->lang('Table'),
            "Columns" => $this->trans->lang('Column'),
            "Procedures" => $this->trans->lang('Routine'),
        ];
        foreach ($contexts as $context => $desc) {
            foreach ($features[$context] as $privilege => $comment) {
                $detail = [$desc, $this->util->html($privilege)];
                // echo "<tr><td" . ($desc ? ">$desc<td" : " colspan='2'") .
                //     ' lang="en" title="' . $this->util->html($comment) . '">' . $this->util->html($privilege);
                $i = 0;
                foreach ($grants as $object => $grant) {
                    $name = "'grants[$i][" . $this->util->html(\strtoupper($privilege)) . "]'";
                    $value = $grant[\strtoupper($privilege)] ?? false;
                    if ($context == "Server Admin" && $object != (isset($grants["*.*"]) ? "*.*" : ".*")) {
                        $detail[] = '';
                    }
                    // elseif(isset($values["grant"]))
                    // {
                    //     $detail[] = "<select name=$name><option><option value='1'" .
                    //         ($value ? " selected" : "") . ">" . $this->trans->lang('Grant') .
                    //         "<option value='0'" . ($value == "0" ? " selected" : "") . ">" .
                    //         $this->trans->lang('Revoke') . "</select>";
                    // }
                    else {
                        $detail[] = "<input type='checkbox' name=$name" . ($value ? " checked />" : " />");
                    }
                    $i++;
                }
                $privileges[] = $detail;
            }
        }

        return $privileges;
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
            $created = $this->driver->execute($create);
            $dropped = $this->driver->execute($drop);
            // $this->executeSavedQuery(!($created && $this->driver->execute($drop)));
            if (!$dropped && $created) {
                $this->driver->execute($dropCreated);
            }
            return 'altered';
        }
        $this->executeSavedQuery(!($this->driver->execute($test) &&
            $this->driver->execute($dropTest) &&
            $this->driver->execute($drop) && $this->driver->execute($create)));
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
