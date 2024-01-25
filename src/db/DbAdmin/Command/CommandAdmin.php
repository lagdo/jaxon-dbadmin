<?php

namespace Lagdo\DbAdmin\Db\DbAdmin\Command;

use Lagdo\DbAdmin\Driver\Db\ConnectionInterface;
use Lagdo\DbAdmin\Driver\Entity\QueryEntity;
use Lagdo\DbAdmin\Db\DbAdmin\AbstractAdmin;

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
     * @var array
     */
    protected $results;

    /**
     * Open a second connection to the server
     *
     * @return ConnectionInterface|null
     */
    private function connection(): ?ConnectionInterface
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
     * @param array $row
     * @param array $blobs
     * @param array $types
     *
     * @return array
    */
    protected function values(array $row, array $blobs, array $types): array
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
    private function message($statement, int $limit): string
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
     * Print select result
     * From editing.inc.php
     *
     * @param mixed $statement
     * @param int $limit
     *
     * @return array
    */
    protected function select($statement, int $limit = 0): array
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
     * @param string $query       The query to execute
     * @param int    $limit         The max number of rows to return
     * @param bool   $errorStops    Stop executing the requests in case of error
     * @param bool   $onlyErrors    Return only errors
     *
     * @return bool
     */
    private function executeCommand(string $query, int $limit, bool $errorStops, bool $onlyErrors): bool
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

            $this->results[] = \compact('query', 'errors', 'messages', 'select');
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
    public function executeCommands(string $queries, int $limit, bool $errorStops, bool $onlyErrors): array
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

        // $timestamps = [];
        // $total_start = \microtime(true);
        // \parse_str($_COOKIE['adminer_export'], $adminer_export);
        // $dump_format = $this->util->dumpFormat();
        // unset($dump_format['sql']);

        $this->results = [];
        $commands = 0;
        $errors = 0;
        $queryEntity = new QueryEntity($queries);
        while ($this->driver->parseQueries($queryEntity)) {
            $commands++;
            if (!$this->executeCommand($queryEntity->query, $limit, $errorStops, $onlyErrors)) {
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

        return ['results' => $this->results, 'messages' => $messages];
    }
}
