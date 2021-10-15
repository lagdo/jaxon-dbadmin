<?php

namespace Lagdo\DbAdmin\DbAdmin;

use Lagdo\DbAdmin\Driver\Db\ConnectionInterface;

/**
 * Admin command functions
 */
class CommandAdmin extends AbstractAdmin
{
    /**
     * Connection for exploring indexes and EXPLAIN (to not replace FOUND_ROWS())
     * //! PDO - silent error
     *
     * @var ConnectionInterface
     */
    protected $connection = null;

    /**
     * Open a second connection to the server
     *
     * @return ConnectionInterface|null
     */
    private function connection()
    {
        if ($this->connection === null && $this->driver->database() !== '') {
            // Connection for exploring indexes and EXPLAIN (to not replace FOUND_ROWS())
            //! PDO - silent error
            $connection = $this->driver->createConnection();
            $connection->open($this->driver->database(), $this->driver->schema());
            $this->connection = $connection;
        }
        return $this->connection;
    }

    /**
     * Print select result
     * From editing.inc.php
     *
     * @param mixed
     * @param array
     * @param int
     *
     * @return array
    */
    protected function select($statement, $orgtables = [], $limit = 0)
    {
        $links = []; // colno => orgtable - create links from these columns
        $indexes = []; // orgtable => array(column => colno) - primary keys
        $columns = []; // orgtable => array(column => ) - not selected columns in primary key
        $blobs = []; // colno => bool - display bytes for blobs
        $types = []; // colno => type - display char in <code>
        $tables = []; // table => orgtable - mapping to use in EXPLAIN

        $colCount = 0;
        $rowCount = 0;
        $details = [];
        while (($limit === 0 || $rowCount < $limit) && ($row = $statement->fetchRow())) {
            $colCount = \count($row);
            $rowCount++;
            $detail = [];
            foreach ($row as $key => $val) {
                $link = "";
                if (isset($links[$key]) && !$columns[$links[$key]]) {
                    if ($orgtables && $this->driver->jush() == "sql") { // MySQL EXPLAIN
                        $table = $row[\array_search("table=", $links)];
                        $link = /*ME .*/ $links[$key] .
                            \urlencode($orgtables[$table] != "" ? $orgtables[$table] : $table);
                    } else {
                        $link = /*ME .*/ "edit=" . \urlencode($links[$key]);
                        foreach ($indexes[$links[$key]] as $col => $j) {
                            $link .= "&where" . \urlencode("[" .
                                $this->util->bracketEscape($col) . "]") . "=" . \urlencode($row[$j]);
                        }
                    }
                } elseif ($this->util->isUrl($val)) {
                    $link = $val;
                }
                if ($val === null) {
                    $val = "<i>NULL</i>";
                } elseif (isset($blobs[$key]) && $blobs[$key] && !$this->util->isUtf8($val)) {
                    //! link to download
                    $val = "<i>" . $this->trans->lang('%d byte(s)', \strlen($val)) . "</i>";
                } else {
                    $val = $this->util->html($val);
                    if (isset($types[$key]) && $types[$key] == 254) { // 254 - char
                        $val = "<code>$val</code>";
                    }
                }
                $detail[$key] = $val;
            }
            $details[] = $detail;
        }
        $message = $this->trans->lang('No rows.');
        if ($rowCount > 0) {
            $numRows = $statement->numRows;
            $message = ($numRows ? ($limit && $numRows > $limit ?
                $this->trans->lang('%d / ', $limit) :
                "") . $this->trans->lang('%d row(s)', $numRows) : "");
        }

        // Table header
        $headers = [];
        $connection = $this->connection();
        for ($j = 0; $j < $colCount; $j++) {
            $field = $statement->fetchField();
            $name = $field->name();
            $orgtable = $field->orgTable();
            $orgname = $field->orgName();
            // PostgreSQL fix: the table field can be missing.
            $tables[$field->tableName()] = $orgtable;
            if ($orgtables && $this->driver->jush() == "sql") { // MySQL EXPLAIN
                $links[$j] = ($name == "table" ? "table=" : ($name == "possible_keys" ? "indexes=" : null));
            } elseif ($orgtable != "") {
                if (!isset($indexes[$orgtable])) {
                    // find primary key in each table
                    $indexes[$orgtable] = [];
                    foreach ($this->driver->indexes($orgtable, $connection) as $index) {
                        if ($index->type == "PRIMARY") {
                            $indexes[$orgtable] = \array_flip($index->columns);
                            break;
                        }
                    }
                    $columns[$orgtable] = $indexes[$orgtable];
                }
                if (isset($columns[$orgtable][$orgname])) {
                    unset($columns[$orgtable][$orgname]);
                    $indexes[$orgtable][$orgname] = $j;
                    $links[$j] = $orgtable;
                }
            }
            if ($field->isBinary()) {
                $blobs[$j] = true;
            }
            $types[$j] = $field->type(); // Some drivers don't set the type field.
            $headers[] = $this->util->html($name);
        }

        return \compact('tables', 'headers', 'details', 'message');
    }

    /**
     * Execute a set of queries
     *
     * @param string $queries       The queries to execute
     * @param int    $limit         The max number of rows to return
     * @param bool   $errorStops    Stop executing the requests in case of error
     * @param bool   $onlyErrors    Return only errors
     *
     * @return array
     */
    public function executeCommands(string $queries, int $limit, bool $errorStops, bool $onlyErrors)
    {
        if (\function_exists('memory_get_usage')) {
            // @ - may be disabled, 2 - substr and trim, 8e6 - other variables
            @\ini_set("memory_limit", \max(
                $this->util->iniBytes("memory_limit"),
                2 * \strlen($queries) + \memory_get_usage() + 8e6
            ));
        }

        // if($queries != "" && \strlen($queries) < 1e6) { // don't add big queries
        // 	$q = $queries . (\preg_match("~;[ \t\r\n]*\$~", $queries) ? "" : ";"); //! doesn't work with DELIMITER |
        // 	if(!$history || \reset(\end($history)) != $q) { // no repeated queries
        // 		\restart_session();
        // 		$history[] = [$q, \time()]; //! add elapsed time
        // 		\set_session("queries", $history_all); // required because reference is unlinked by stop_session()
        // 		\stop_session();
        // 	}
        // }

        $space = "(?:\\s|/\\*[\s\S]*?\\*/|(?:#|-- )[^\n]*\n?|--\r?\n)";
        $delimiter = ";";
        $offset = 0;
        $empty = true;

        $commands = 0;
        $timestamps = [];
        // TODO: Move this to driver implementations
        $parse = '[\'"' .
            ($this->driver->jush() == "sql" ? '`#' :
            ($this->driver->jush() == "sqlite" ? '`[' :
            ($this->driver->jush() == "mssql" ? '[' : ''))) . ']|/\*|-- |$' .
            ($this->driver->jush() == "pgsql" ? '|\$[^$]*\$' : '');
        // $total_start = \microtime(true);
        // \parse_str($_COOKIE["adminer_export"], $adminer_export);
        // $dump_format = $this->util->dumpFormat();
        // unset($dump_format["sql"]);

        $results = [];
        while ($queries != "") {
            if ($offset == 0 && \preg_match("~^$space*+DELIMITER\\s+(\\S+)~i", $queries, $match)) {
                $delimiter = $match[1];
                $queries = \substr($queries, \strlen($match[0]));
                continue;
            }

            // should always match
            \preg_match('(' . \preg_quote($delimiter) . "\\s*|$parse)", $queries, $match, PREG_OFFSET_CAPTURE, $offset);
            list($found, $pos) = $match[0];

            if (!$found && \rtrim($queries) == "") {
                break;
            }
            $offset = $pos + \strlen($found);

            if ($found && \rtrim($found) != $delimiter) {
                // find matching quote or comment end
                while (\preg_match('(' . ($found == '/*' ? '\*/' : ($found == '[' ? ']' :
                    (\preg_match('~^-- |^#~', $found) ? "\n" : \preg_quote($found) .
                    "|\\\\."))) . '|$)s', $queries, $match, PREG_OFFSET_CAPTURE, $offset)) {
                    //! respect sql_mode NO_BACKSLASH_ESCAPES
                    $s = $match[0][0];
                    $offset = $match[0][1] + \strlen($s);
                    if ($s[0] != "\\") {
                        break;
                    }
                }
                continue;
            }

            // end of a query
            $errors = [];
            $messages = [];
            $select = null;

            $empty = false;
            $q = \substr($queries, 0, $pos);
            $commands++;
            // $print = "<pre id='sql-$commands'><code class='jush-$this->driver->jush()'>" .
            //     $this->util->sqlCommandQuery($q) . "</code></pre>\n";
            if ($this->driver->jush() == "sqlite" && \preg_match("~^$space*+ATTACH\\b~i", $q, $match)) {
                // PHP doesn't support setting SQLITE_LIMIT_ATTACHED
                // $errors[] = " <a href='#sql-$commands'>$commands</a>";
                $errors[] = $this->trans->lang('ATTACH queries are not supported.');
                $results[] = [
                    'query' => $q,
                    'errors' => $errors,
                    'messages' => $messages,
                    'select' => $select,
                ];
                if ($errorStops) {
                    break;
                }
            } else {
                // if(!$onlyErrors)
                // {
                //     echo $print;
                //     \ob_flush();
                //     \flush(); // can take a long time - show the running query
                // }
                $start = \microtime(true);
                //! don't allow changing of character_set_results, convert encoding of displayed query
                $connection = $this->connection();
                if ($this->driver->multiQuery($q) && $connection !== null && \preg_match("~^$space*+USE\\b~i", $q)) {
                    $connection->query($q);
                }

                do {
                    $statement = $this->driver->storedResult();

                    if ($this->driver->hasError()) {
                        $error = $this->driver->error();
                        if ($this->driver->hasErrno()) {
                            $error = "(" . $this->driver->errno() . "): $error";
                        }
                        $errors[] = $error;
                    } else {
                        $affected = $this->driver->affectedRows(); // getting warnigns overwrites this
                        if (\is_object($statement)) {
                            if (!$onlyErrors) {
                                $select = $this->select($statement, [], $limit);
                                $messages[] = $select['message'];
                            }
                        } else {
                            if (!$onlyErrors) {
                                // $title = $this->util->html($this->driver->info());
                                $messages[] = $this->trans->lang('Query executed OK, %d row(s) affected.', $affected); //  . "$time";
                            }
                        }
                    }

                    $results[] = [
                        'query' => $q,
                        'errors' => $errors,
                        'messages' => $messages,
                        'select' => $select,
                    ];

                    if ($this->driver->hasError() && $errorStops) {
                        break 2;
                    }

                    $start = \microtime(true);
                } while ($this->driver->nextResult());
            }

            $queries = \substr($queries, $offset);
            $offset = 0;
        }

        if ($empty) {
            $messages[] = $this->trans->lang('No commands to execute.');
        } elseif ($onlyErrors) {
            $messages[] =  $this->trans->lang('%d query(s) executed OK.', $commands - \count($errors));
            // $timestamps[] = $this->trans->formatTime($total_start);
        }
        // elseif($errors && $commands > 1)
        // {
        //     $errors[] = $this->trans->lang('Error in query') . ": " . \implode("", $errors);
        // }
        //! MS SQL - SET SHOWPLAN_ALL OFF

        return \compact('results', 'messages', 'errors', 'timestamps');
    }
}
