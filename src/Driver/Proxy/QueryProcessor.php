<?php

namespace Lagdo\DbAdmin\Support\Driver\Proxy;

use Lagdo\DbAdmin\Driver\Sql\Connection\AbstractConnection;
use Lagdo\DbAdmin\Driver\Sql\Dto\QueryCodeDto;
use Lagdo\DbAdmin\Support\Driver\AbstractDriverProxy;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dql\SelectResult;
use Lagdo\DbAdmin\Support\Driver\UiDto\ExecOptions;
use Lagdo\DbAdmin\Support\Driver\UiDto\ExecResultDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\RowsetDto;
use Lagdo\DbAdmin\Support\Service\Admin\QueryLogger;
use Lagdo\DbAdmin\Support\Service\TimerService;
use Generator;

use function count;
use function is_array;
use function preg_match;

/**
 * Execute one or more queries, and format the results.
 * Handles durations, batches, transactions and audit logging.
 */
class QueryProcessor extends AbstractDriverProxy
{
    /**
     * Connection for exploring indexes and EXPLAIN (to not replace FOUND_ROWS())
     * //! PDO - silent error
     *
     * @var AbstractConnection
     */
    private $connection = null;

    /**
     * @var TimerService
     */
    private TimerService $timer;

    /**
     * @var QueryLogger|null
     */
    private QueryLogger|null $queryLogger;

    /**
     * @var SelectResult
     */
    private SelectResult $selectResult;

    /**
     * @var bool
     */
    private bool $timerEnabled = false;

    /**
     * @var bool
     */
    private bool $loggerEnabled = false;

    /**
     * @var int
     */
    private int $batchSize = 20;

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
     * @param bool $enabled
     *
     * @return static
     */
    public function withTimer(bool $enabled): static
    {
        $this->timerEnabled = $enabled;
        return $this;
    }

    /**
     * @param bool $enabled
     *
     * @return static
     */
    public function withLogger(bool $enabled): static
    {
        $this->loggerEnabled = $enabled;
        return $this;
    }

    /**
     * @return SelectResult
     */
    private function result(): SelectResult
    {
        return $this->selectResult ??= new SelectResult($this);
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
            $database = $this->engine()->database();
            $schema = $this->engine()->schema();
            $this->connection = $this->engine()->openNewConnection($database, $schema);
        }
    }

    /**
     * Execute a query, and return the number of errors
     *
     * @param array $query
     * @param ExecResultDto $resultDto
     *
     * @return void
     */
    private function executeSpecialQuery(array $query, ExecResultDto $resultDto): void
    {
        // Special type of queries (upsert only for now).
        [$type, [$update, $insert]] = $query;
        if ($type !== 'upsert') {
            return; // Skip queries with unknown type.
        }

        // Execute the update query of the upsert.
        $resultDto->queries++;
        if ($this->engine()->executeQuery($update)->hasError()) {
            $resultDto->errors++;
            return;
        }
        if ($this->engine()->affectedRows() > 0) {
            return; // The update was successful.
        }

        // Execute the insert query of the upsert.
        $resultDto->queries++;
        if ($this->engine()->executeQuery($insert)->hasError()) {
            $resultDto->errors++;
        }
    }

    /**
     * @param ExecOptions $options
     * @param string $query
     *
     * @return void
     */
    private function executeUseStatement(ExecOptions $options, string $query): void
    {
        if ($this->connection === null || $options->select !== null) {
            return;
        }

        $space = $this->engine()->spaceRegex();
        if (preg_match("~^$space*+USE\\b~i", $query)) {
            $this->connection->executeQuery($query);
        }
    }

    /**
     * @param ExecResultDto $resultDto
     *
     * @return void
     */
    public function executeQueryBatch(ExecResultDto $resultDto): void
    {
        if (count($resultDto->batchBuffer) === 0) {
            return;
        }

        $queries = implode(";", $resultDto->batchBuffer);
        $resultDto->batchBuffer = [];
        $result = $this->engine()->executeMultiQuery($queries);
        if ($result->hasError()) {
            $resultDto->errors++;
        }
    }

    /**
     * Execute a query.
     *
     * @param string|array $query
     * @param ExecOptions $options
     * @param ExecResultDto $resultDto
     *
     * @return void
     */
    private function executeQuery(string|array $query,
        ExecOptions $options, ExecResultDto $resultDto): void
    {
        if (is_array($query)) {
            $this->executeSpecialQuery($query, $resultDto);
            return;
        }

        $resultDto->queries++;
        $this->executeUseStatement($options, $query);

        if ($options->inBatch) {
            $resultDto->batchBuffer[] = $query;
            if ($this->batchSize > 0 && count($resultDto->batchBuffer) >= $this->batchSize) {
                // Execute the batched queries.
                $this->executeQueryBatch($resultDto);
            }

            return;
        }

        $result = $this->engine()->executeQuery($query);
        if ($result->hasError()) {
            $resultDto->errors++;
        }

        if (!$options->saveResults) {
            return;
        }

        $rowset = match(true) {
            $result->hasError() =>
                new RowsetDto(error: $this->engine()->errorMessage()),
            $options->onlyErrors => null, // Rowset skipped.
            $options->select !== null =>
                $this->result()->getSelectRowset($result, $options->select),
            default =>
                $this->result()->getQueryRowset($result, $options->limit),
        };
        if ($rowset !== null) {
            $rowset->query = $query;
            $resultDto->rowsets[] = $rowset;
        }
    }

    /**
     * Execute a set of queries.
     *
     * @param array|Generator $queries
     * @param ExecOptions $options
     *
     * @return ExecResultDto
     */
    public function executeQueries(array|Generator $queries, ExecOptions $options): ExecResultDto
    {
        // The second connection must be created before executing the queries.
        $this->openNewConnection();

        $options->stopOnError = $options->stopOnError || $options->inTransaction;

        if ($options->inTransaction) {
            $this->engine()->begin();
        }
        if ($this->loggerEnabled && $this->queryLogger !== null) {
            $this->queryLogger->setCategoryToEditor();
        }

        $resultDto = new ExecResultDto();
        foreach ($queries as $query) {
            if ($this->timerEnabled && $this->timer !== null) {
                $this->timer->start();
            }

            $this->executeQuery($query, $options, $resultDto);

            if ($this->timerEnabled && $this->timer !== null) {
                $resultDto->duration += $this->timer->duration();
            }
            if ($resultDto->errors > 0 && $options->stopOnError) {
                break;
            }
        }

        if ($options->inTransaction) {
            $resultDto->errors > 0 ? $this->engine()->rollback() : $this->engine()->commit();
        }

        return $resultDto;
    }

    /**
     * Execute a set of queries from the library.
     *
     * @param array $queries
     *
     * @return ExecResultDto
     */
    public function executeLibraryQueries(array $queries): ExecResultDto
    {
        if (isset($queries['error'])) {
            $resultDto = new ExecResultDto();
            $resultDto->errors = 1;
            $resultDto->error = $queries['error'];
            return $resultDto;
        }

        $options = new ExecOptions(true, true);
        $options->setExecOptions(true, false, false);
        return $this->withTimer(false)
            ->withLogger(false)
            ->executeQueries($queries['queries'] ?? [$queries['query']], $options);
    }

    /**
     * @param QueryCodeDto $queryDto
     * @param ExecOptions $options
     *
     * @return ExecResultDto
     */
    public function executeUserQueries(QueryCodeDto $queryDto, ExecOptions $options): ExecResultDto
    {
        $queries = $this->statement()->splitQueries($queryDto);
        $result = $this->withTimer(true)
            ->withLogger(true)
            ->executeQueries($queries, $options);
        // There might be some remaining queries in the batch buffer.
        if ($options->inBatch) {
            $this->executeQueryBatch($result);
        }

        return $result;
    }
}
