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
     * @param mixed $value
     *
     * @return string
    */
    // protected function editLink($value)
    // {
    //     $link = '';
    //     if (isset($links[$key]) && !$columns[$links[$key]]) {
    //         if ($orgtables && $this->driver->jush() == 'sql') { // MySQL EXPLAIN
    //             $table = $row[\array_search('table=', $links)];
    //             $link = /*ME .*/ $links[$key] .
    //                 \urlencode($orgtables[$table] != '' ? $orgtables[$table] : $table);
    //         } else {
    //             $link = /*ME .*/ 'edit=' . \urlencode($links[$key]);
    //             foreach ($indexes[$links[$key]] as $col => $j) {
    //                 $link .= '&where' . \urlencode('[' .
    //                     $this->util->bracketEscape($col) . ']') . '=' . \urlencode($row[$j]);
    //             }
    //         }
    //     } elseif ($this->util->isUrl($val)) {
    //         $link = $val;
    //     }
    // }

    /**
     * @param array $row
     * @param array $blobs
     * @param array $types
     *
     * @return string
    */
    protected function values(array $row, array $blobs, array $types)
    {
        $values = [];
        foreach ($row as $key => $value) {
            // $link = $this->editLink($val);
            if ($value === null) {
                $value = '<i>NULL</i>';
            } elseif (isset($blobs[$key]) && $blobs[$key] && !$this->util->isUtf8($value)) {
                //! link to download
                $value = '<i>' . $this->trans->lang('%d byte(s)', \strlen($value)) . '</i>';
            } else {
                $value = $this->util->html($value);
                if (isset($types[$key]) && $types[$key] == 254) { // 254 - char
                    $value = "<code>$value</code>";
                }
            }
            $values[$key] = $value;
        }
        return $values;
    }

    /**
     * @param mixed $statement
     * @param int $limit
     *
     * @return string
    */
    private function message($statement, int $limit)
    {
        $numRows = $statement->rowCount();
        $message = '';
        if ($numRows > 0) {
            if ($limit > 0 && $numRows > $limit) {
                $message = $this->trans->lang('%d / ', $limit);
            }
            $message .= $this->trans->lang('%d row(s)', $numRows);
        }
        return $message;
    }

    /**
     * @param mixed $field
     * @param array $orgtables
     *
     * @return string
    */
    // protected function indexes($field, array $orgtables)
    // {
    //     static $links = []; // colno => orgtable - create links from these columns
    //     static $indexes = []; // orgtable => array(column => colno) - primary keys
    //     static $columns = []; // orgtable => array(column => ) - not selected columns in primary key

    //     if (!empty($this->orgtables) && $this->driver->jush() == 'sql') { // MySQL EXPLAIN
    //         $links[$j] = ($name == 'table' ? 'table=' : ($name == 'possible_keys' ? 'indexes=' : null));
    //     } elseif ($orgtable != '') {
    //         if (!isset($indexes[$orgtable])) {
    //             // find primary key in each table
    //             $indexes[$orgtable] = [];
    //             foreach ($this->driver->indexes($orgtable, $connection) as $index) {
    //                 if ($index->type == 'PRIMARY') {
    //                     $indexes[$orgtable] = \array_flip($index->columns);
    //                     break;
    //                 }
    //             }
    //             $columns[$orgtable] = $indexes[$orgtable];
    //         }
    //         if (isset($columns[$orgtable][$orgname])) {
    //             unset($columns[$orgtable][$orgname]);
    //             $indexes[$orgtable][$orgname] = $j;
    //             $links[$j] = $orgtable;
    //         }
    //     }
    // }

    /**
     * Print select result
     * From editing.inc.php
     *
     * @param mixed $statement
     * @param int $limit
     *
     * @return array
    */
    protected function select($statement, $limit = 0)
    {
        // No resultset
        if ($statement === true) {
            $affected = $this->driver->affectedRows();
            $message = $this->trans->lang('Query executed OK, %d row(s) affected.', $affected); //  . "$time";
            return [null, [$message]];
        }
        // Fetch the first row.
        if (!($row = $statement->fetchRow())) {
            // Empty resultset.
            $message = $this->trans->lang('No rows.');
            return [null, [$message]];
        }

        $blobs = []; // colno => bool - display bytes for blobs
        $types = []; // colno => type - display char in <code>
        $tables = []; // table => orgtable - mapping to use in EXPLAIN
        $headers = [];
        $details = [];
        // Table headers.
        $colCount = \count($row);
        for ($j = 0; $j < $colCount; $j++) {
            $field = $statement->fetchField();
            // PostgreSQL fix: the table field can be missing.
            $tables[$field->tableName()] = $field->orgTable();
            // $this->indexes($field);
            if ($field->isBinary()) {
                $blobs[$j] = true;
            }
            $types[$j] = $field->type(); // Some drivers don't set the type field.
            $headers[] = $this->util->html($field->name());
        }

        // Table rows (the first was already fetched).
        $rowCount = 0;
        do {
            $rowCount++;
            $details[] = $this->values($row, $blobs, $types);
        } while (($limit === 0 || $rowCount < $limit) && ($row = $statement->fetchRow()));

        $message = $this->message($statement, $limit);
        return [\compact('tables', 'headers', 'details'), [$message]];
    }

    /**
     * @param string $queries       The queries to execute
     * @param int    $offset
     *
     * @return int
     */
    private function nextQueryPos(string &$queries, int &$offset)
    {
        static $delimiter = ';';

        $space = "(?:\\s|/\\*[\s\S]*?\\*/|(?:#|-- )[^\n]*\n?|--\r?\n)";
        if ($offset == 0 && \preg_match("~^$space*+DELIMITER\\s+(\\S+)~i", $queries, $match)) {
            $delimiter = $match[1];
            $queries = \substr($queries, \strlen($match[0]));
            return 0;
        }

        // TODO: Move this to driver implementations
        $parse = '[\'"' .
            ($this->driver->jush() == "sql" ? '`#' :
            ($this->driver->jush() == "sqlite" ? '`[' :
            ($this->driver->jush() == "mssql" ? '[' : ''))) . ']|/\*|-- |$' .
            ($this->driver->jush() == "pgsql" ? '|\$[^$]*\$' : '');
        // should always match
        \preg_match('(' . \preg_quote($delimiter) . "\\s*|$parse)", $queries, $match, PREG_OFFSET_CAPTURE, $offset);
        list($found, $pos) = $match[0];

        if (!\is_string($found) && \rtrim($queries) == '') {
            return -1;
        }
        $offset = $pos + \strlen($found);

        if (empty($found) || \rtrim($found) == $delimiter) {
            return \intval($pos);
        }
        // find matching quote or comment end
        while (\preg_match('(' . ($found == '/*' ? '\*/' : ($found == '[' ? ']' :
            (\preg_match('~^-- |^#~', $found) ? "\n" : \preg_quote($found) . "|\\\\."))) . '|$)s',
            $queries, $match, PREG_OFFSET_CAPTURE, $offset)) {
            //! respect sql_mode NO_BACKSLASH_ESCAPES
            $s = $match[0][0];
            $offset = $match[0][1] + \strlen($s);
            if ($s[0] != "\\") {
                break;
            }
        }
        return 0;
    }

    /**
     * @param string $query       The query to execute
     * @param array  $results
     * @param int    $limit         The max number of rows to return
     * @param bool   $errorStops    Stop executing the requests in case of error
     * @param bool   $onlyErrors    Return only errors
     *
     * @return bool
     */
    private function executeQuery(string $query, array &$results, int $limit, bool $errorStops, bool $onlyErrors)
    {
        //! Don't allow changing of character_set_results, convert encoding of displayed query
        if ($this->driver->multiQuery($query) && ($connection = $this->connection()) !== null) {
            $connection->execUseQuery($query);
        }

        do {
            $select = null;
            $errors = [];
            $messages = [];
            $statement = $this->driver->storedResult();

            if ($this->driver->hasError()) {
                $errors[] = $this->driver->errorMessage();
            } elseif (!$onlyErrors) {
                [$select, $messages] = $this->select($statement, $limit);
            }

            $results[] = \compact('query', 'errors', 'messages', 'select');

            if ($this->driver->hasError() && $errorStops) {
                return false;
            }

            // $start = \microtime(true);
        } while ($this->driver->nextResult());

        return true;
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
            try {
                \ini_set('memory_limit', \max($this->util->iniBytes('memory_limit'),
                    2 * \strlen($queries) + \memory_get_usage() + 8e6));
            }
            catch(\Exception $e) {
                // Do nothing if the option is not modified.
            }
        }

        // if($queries != '' && \strlen($queries) < 1e6) { // don't add big queries
        // 	$q = $queries . (\preg_match("~;[ \t\r\n]*\$~", $queries) ? '' : ';'); //! doesn't work with DELIMITER |
        // 	if(!$history || \reset(\end($history)) != $q) { // no repeated queries
        // 		\restart_session();
        // 		$history[] = [$q, \time()]; //! add elapsed time
        // 		\set_session('queries', $history_all); // required because reference is unlinked by stop_session()
        // 		\stop_session();
        // 	}
        // }

        $offset = 0;
        $commands = 0;
        $errors = 0;
        // $timestamps = [];
        // $total_start = \microtime(true);
        // \parse_str($_COOKIE['adminer_export'], $adminer_export);
        // $dump_format = $this->util->dumpFormat();
        // unset($dump_format['sql']);

        $results = [];
        while ($queries != '') {
            $pos = $this->nextQueryPos($queries, $offset);
            if ($pos < 0) {
                break;
            }
            if ($pos === 0) {
                continue;
            }

            // end of a query

            $query = \substr($queries, 0, $pos);
            $queries = \substr($queries, $offset);
            $offset = 0;
            $commands++;
            if (!$this->executeQuery($query, $results, $limit, $errorStops, $onlyErrors)) {
                $errors++;
                if ($errorStops) {
                    break;
                }
            }
        }

        $messages = [];
        if ($commands === 0) {
            $messages[] = $this->trans->lang('No commands to execute.');
        } elseif ($onlyErrors) {
            $messages[] =  $this->trans->lang('%d query(s) executed OK.', $commands - $errors);
        }

        return \compact('results', 'messages');
    }
}
