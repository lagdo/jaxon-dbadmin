<?php

namespace Lagdo\DbAdmin\Db\Facades;

use Lagdo\DbAdmin\Command\LogWriter;
use Lagdo\DbAdmin\Command\TimerService;
use Lagdo\DbAdmin\Driver\Db\ConnectionInterface;
use Lagdo\DbAdmin\Driver\Entity\QueryEntity;

use function compact;
use function count;
use function function_exists;
use function ini_set;
use function max;
use function memory_get_usage;
use function preg_match;
use function strlen;

/**
 * Facade to command functions
 */
class CommandFacade extends AbstractFacade
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
     * @var float
     */
    protected $duration;

    /**
     * Initialize the facade
     *
     * @param AbstractFacade $dbFacade
     * @param TimerService $timer
     * @param LogWriter|null $queryLogger
     */
    public function __construct(AbstractFacade $dbFacade,
        protected TimerService $timer, protected LogWriter|null $queryLogger)
    {
        parent::__construct($dbFacade);
    }

    /**
     * Open a second connection to the server
     *
     * @return void
     */
    private function openSecondConnection()
    {
        // Connection for exploring indexes and EXPLAIN (to not replace FOUND_ROWS())
        //! PDO - silent error
        // TODO: use this connection to execute EXPLAIN queries.
        if ($this->connection === null && $this->driver->database() !== '') {
            $this->connection = $this->driver->connectToDatabase(
                $this->driver->database(), $this->driver->schema());
        }
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
            } elseif (isset($blobs[$key]) && $blobs[$key] && !$this->utils->str->isUtf8($value)) {
                //! link to download
                $value = '<i>' . $this->utils->trans->lang('%d byte(s)', strlen($value)) . '</i>';
            } else {
                $value = $this->utils->str->html($value);
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
                $message = $this->utils->trans->lang('%d / ', $limit);
            }
            $message .= $this->utils->trans->lang('%d row(s)', $numRows);
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
            $message = $this->utils->trans
                ->lang('Query executed OK, %d row(s) affected.', $affected); //  . "$time";
            return [null, [$message]];
        }
        // Fetch the first row.
        if (!($row = $statement->fetchRow())) {
            // Empty resultset.
            $message = $this->utils->trans->lang('No rows.');
            return [null, [$message]];
        }

        $blobs = []; // colno => bool - display bytes for blobs
        $types = []; // colno => type - display char in <code>
        $tables = []; // table => orgtable - mapping to use in EXPLAIN
        $headers = [];
        $details = [];
        // Table headers.
        $colCount = count($row);
        for ($j = 0; $j < $colCount; $j++) {
            $field = $statement->fetchField();
            // PostgreSQL fix: the table field can be missing.
            $tables[$field->tableName()] = $field->orgTable();
            // $this->indexes($field);
            if ($field->isBinary()) {
                $blobs[$j] = true;
            }
            $types[$j] = $field->type(); // Some drivers don't set the type field.
            $headers[] = $this->utils->str->html($field->name());
        }

        // Table rows (the first was already fetched).
        $rowCount = 0;
        do {
            $rowCount++;
            $details[] = $this->values($row, $blobs, $types);
        } while (($limit === 0 || $rowCount < $limit) && ($row = $statement->fetchRow()));

        $message = $this->message($statement, $limit);
        return [compact('tables', 'headers', 'details'), [$message]];
    }

    /**
     * @param QueryEntity $queryEntity
     *
     * @return bool
     */
    private function executeCommand(QueryEntity $queryEntity): bool
    {
        if ($this->queryLogger !== null) {
            $this->queryLogger->setCategoryToHistory();
        }
        $this->timer->start();
        //! Don't allow changing of character_set_results, convert encoding of displayed query
        $space = $this->utils->str->spaceRegex();
        $succeeded = $this->driver->multiQuery($queryEntity->query);
        if ($succeeded && $this->connection !== null &&
            preg_match("~^$space*+USE\\b~i", $queryEntity->query)) {
            $this->connection->query($queryEntity->query);
        }
        $this->duration += $this->timer->duration();

        do {
            $select = null;
            $errors = [];
            $messages = [];
            $statement = $this->driver->storedResult();

            if ($this->driver->connection()->hasError()) {
                $errors[] = $this->driver->connection()->errorMessage();
            } elseif (!$queryEntity->onlyErrors) {
                [$select, $messages] = $this->select($statement, $queryEntity->limit);
            }

            $result = compact('errors', 'messages', 'select');
            $result['query'] = $queryEntity->query;
            $this->results[] = $result;
            if ($this->driver->connection()->hasError() && $queryEntity->errorStops) {
                return false;
            }
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
        if (function_exists('memory_get_usage')) {
            // @ - may be disabled, 2 - substr and trim, 8e6 - other variables
            try {
                ini_set('memory_limit', max($this->admin->iniBytes('memory_limit'),
                    2 * strlen($queries) + memory_get_usage() + 8e6));
            }
            catch(\Exception $e) {
                // Do nothing if the option is not modified.
            }
        }

        // The second connection must be created before executing the queries.
        $this->openSecondConnection();

        $this->results = [];
        $this->duration = 0;
        $commands = 0;
        $errors = 0;
        $queryEntity = new QueryEntity($queries, $limit, $errorStops, $onlyErrors);
        while ($this->driver->parseQueries($queryEntity)) {
            $commands++;
            if (!$this->executeCommand($queryEntity)) {
                $errors++;
                if ($errorStops) {
                    break;
                }
            }
        }

        $messages = [];
        if ($commands === 0) {
            $messages[] = $this->utils->trans->lang('No commands to execute.');
        } elseif ($onlyErrors) {
            $messages[] =  $this->utils->trans->lang('%d query(s) executed OK.', $commands - $errors);
        }
        return [
            'results' => $this->results,
            'messages' => $messages,
            'duration' => $this->duration,
        ];
    }
}
