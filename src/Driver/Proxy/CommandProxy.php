<?php

namespace Lagdo\DbAdmin\Support\Driver\Proxy;

use Lagdo\DbAdmin\Support\Driver\AbstractDriverProxy;
use Lagdo\DbAdmin\Support\Service\Admin\QueryLogger;
use Lagdo\DbAdmin\Support\Service\TimerService;
use Lagdo\DbAdmin\Driver\Sql\Connection\AbstractConnection;
use Lagdo\DbAdmin\Driver\Sql\Dto\QueryInputDto;

use function compact;
use function function_exists;
use function ini_set;
use function max;
use function memory_get_usage;
use function preg_match;
use function strlen;

/**
 * Proxy to command functions
 */
class CommandProxy extends AbstractDriverProxy
{
    /**
     * Connection for exploring indexes and EXPLAIN (to not replace FOUND_ROWS())
     * //! PDO - silent error
     *
     * @var AbstractConnection
     */
    protected $connection = null;

    /**
     * @var TimerService
     */
    protected TimerService $timer;

    /**
     * @var QueryLogger|null
     */
    protected QueryLogger|null $queryLogger;

    /**
     * @var array
     */
    protected $results;

    /**
     * @var float
     */
    protected $duration;

    /**
     * @param TimerService $timer
     *
     * @return static
     */
    public function setTimer(TimerService $timer): static
    {
        $this->timer = $timer;
        return $this;
    }

    /**
     * @param QueryLogger|null $queryLogger
     *
     * @return static
     */
    public function setQueryLogger(QueryLogger|null $queryLogger): static
    {
        $this->queryLogger = $queryLogger;
        return $this;
    }

    /**
     * Open a second connection to the server
     *
     * @return void
     */
    private function openNewConnection()
    {
        // Connection for exploring indexes and EXPLAIN (to not replace FOUND_ROWS())
        //! PDO - silent error
        // TODO: use this connection to execute EXPLAIN queries.
        if ($this->connection === null && $this->engine()->database() !== '') {
            $this->connection = $this->engine()->openNewConnection(
                $this->engine()->database(), $this->engine()->schema());
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
            $values[$key] = match(true) {
                $value === null => '<i>NULL</i>',
                //! link to download
                isset($blobs[$key]) && $blobs[$key] && !$this->utils()->str->isUtf8($value) =>
                    '<i>' . $this->utils()->lang('%d byte(s)', strlen($value)) . '</i>',
                isset($types[$key]) && $types[$key] === 254 =>
                    '<code>' . $this->utils()->html($value) . '</code>',
                default => $this->utils()->html($value),
            };
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
                $message = $this->utils()->lang('%d / ', $limit);
            }
            $message .= $this->utils()->lang('%d row(s)', $numRows);
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
            $affected = $this->engine()->affectedRows();
            $message = $this->utils()->trans
                ->lang('Query executed OK, %d row(s) affected.', $affected); //  . "$time";
            return [null, [$message]];
        }
        // Fetch the first row.
        if (!($row = $statement->fetchRow())) {
            // Empty resultset.
            $message = $this->utils()->lang('No rows.');
            return [null, [$message]];
        }

        $blobs = []; // colno => bool - display bytes for blobs
        $types = []; // colno => type - display char in <code>
        $tables = []; // table => orgtable - mapping to use in EXPLAIN
        $headers = [];
        // Important: the values in the row are actually not used.
        $columns = array_map(fn($_) => $statement->fetchColumn(), $row);

        // Use the first row to get the table headers.
        foreach($columns as $column) {
            // PostgreSQL fix: the table field can be missing.
            $tables[$column->tableName()] = $column->orgTable();
            // $this->indexes($column);
            $blobs[] = $column->isBinary();
            $types[] = $column->type(); // Some drivers don't set the type field.
            $headers[] = $this->utils()->html($column->name());
        }

        // Table rows (the first was already fetched).
        $rowCount = 0;
        $details = [];
        do {
            $rowCount++;
            $details[] = $this->values($row, $blobs, $types);
        } while (($limit === 0 || $rowCount < $limit) && ($row = $statement->fetchRow()));

        $message = $this->message($statement, $limit);
        return [compact('tables', 'headers', 'details'), [$message]];
    }

    /**
     * @param QueryInputDto $input
     *
     * @return bool
     */
    private function executeCommand(QueryInputDto $input): bool
    {
        if ($this->queryLogger !== null) {
            $this->queryLogger->setCategoryToEditor();
        }
        $this->timer->start();
        //! Don't allow changing of character_set_results, convert encoding of displayed query
        $space = $this->utils()->str->spaceRegex();
        $succeeded = $this->engine()->multiQuery($input->query);
        if ($succeeded && $this->connection !== null &&
            preg_match("~^$space*+USE\\b~i", $input->query)) {
            $this->connection->query($input->query);
        }
        $this->duration += $this->timer->duration();

        do {
            $select = null;
            $errors = [];
            $messages = [];
            $statement = $this->engine()->storedResult();

            if (!$statement || $this->engine()->hasError()) {
                $errors[] = $this->engine()->errorMessage();
            } elseif (!$input->onlyErrors) {
                [$select, $messages] = $this->select($statement, $input->limit);
            }

            $result = compact('errors', 'messages', 'select');
            $result['query'] = $input->query;
            $this->results[] = $result;
            if ($this->engine()->hasError() && $input->errorStops) {
                return false;
            }
        } while ($this->engine()->nextResult());

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
                ini_set('memory_limit', max($this->utils()->iniBytes('memory_limit'),
                    2 * strlen($queries) + memory_get_usage() + 8e6));
            }
            catch(\Exception $e) {
                // Do nothing if the option is not modified.
            }
        }

        // The second connection must be created before executing the queries.
        $this->openNewConnection();

        $this->results = [];
        $this->duration = 0;
        $commands = 0;
        $errors = 0;
        $input = new QueryInputDto($queries, $limit, $errorStops, $onlyErrors);
        while ($this->statement()->parseQueries($input)) {
            $commands++;
            if (!$this->executeCommand($input)) {
                $errors++;
                if ($errorStops) {
                    break;
                }
            }
        }

        $messages = match(true) {
            $commands === 0 => [
                'message' => $this->utils()->lang('No commands to execute.'),
            ],
            $onlyErrors => [
                'message' => $this->utils()->lang('%d query(s) executed OK.', $commands - $errors),
            ],
            default => [],
        };
        return [
            ...$messages,
            'duration' => $this->duration,
            'results' => $this->results,
        ];
    }
}
