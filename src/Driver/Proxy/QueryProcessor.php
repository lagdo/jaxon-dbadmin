<?php

namespace Lagdo\DbAdmin\Support\Driver\Proxy;

use Lagdo\DbAdmin\Driver\Sql\Connection\AbstractConnection;
use Lagdo\DbAdmin\Support\Driver\AbstractDriverProxy;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dql\SelectDqDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dql\SelectResult;
use Lagdo\DbAdmin\Support\Driver\UiDto\ExecOptions;
use Lagdo\DbAdmin\Support\Driver\UiDto\ExecResultDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\QueryListDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\RowsetDto;
use Lagdo\DbAdmin\Support\Service\Admin\QueryLogger;
use Lagdo\DbAdmin\Support\Service\Query\QuerySplitter;
use Lagdo\DbAdmin\Support\Service\Query\QueryStream;
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
     * @var QuerySplitter
     */
    private QuerySplitter $querySplitter;

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
     * @param QuerySplitter $querySplitter
     *
     * @return static
     */
    public function setQuerySplitter(QuerySplitter $querySplitter): static
    {
        $this->querySplitter = $querySplitter;
        return $this;
    }

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
     * @param string $query
     *
     * @return void
     */
    private function executeUseStatement(string $query): void
    {
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
            $resultDto->error = $this->engine()->errorMessage();
        }
    }

    /**
     * @param string|array $query
     * @param ExecOptions $options
     * @param ExecResultDto $resultDto
     * @param SelectDqDto|null $select
     *
     * @return void
     */
    private function executeQuery(string|array $query, ExecOptions $options,
        ExecResultDto $resultDto, SelectDqDto|null $select): void
    {
        if (is_array($query)) {
            $this->executeSpecialQuery($query, $resultDto);

            return;
        }

        $resultDto->queries++;
        if ($select === null && $this->connection !== null) {
            $this->executeUseStatement($query);
        }

        if ($select === null && $options->inBatch) {
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
            $resultDto->error = $this->engine()->errorMessage();
        }

        if (!$options->saveResults) {
            return;
        }

        $rowset = match(true) {
            $result->hasError() => new RowsetDto(error: $resultDto->error),
            $options->onlyErrors => null, // Rowset skipped.
            $select !== null => $this->result()->getSelectRowset($result, $select),
            default => $this->result()->getQueryRowset($result, $options->limit),
        };
        if ($rowset !== null) {
            $rowset->query = $query;
            $resultDto->rowsets[] = $rowset;
        }
    }

    /**
     * @param array|Generator $queries
     * @param ExecOptions $options
     * @param SelectDqDto|null $select
     *
     * @return ExecResultDto
     */
    private function executeQueries(array|Generator $queries,
        ExecOptions $options, SelectDqDto|null $select = null): ExecResultDto
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

            $this->executeQuery($query, $options, $resultDto, $select);

            if ($this->timerEnabled && $this->timer !== null) {
                $resultDto->duration += $this->timer->duration();
            }
            if ($resultDto->errors > 0 && $options->stopOnError) {
                break;
            }
        }

        // There might be some remaining queries in the batch buffer.
        if ($options->inBatch) {
            $this->executeQueryBatch($resultDto);
        }

        if ($options->inTransaction) {
            $resultDto->errors > 0 ? $this->engine()->rollback() : $this->engine()->commit();
        }

        return $resultDto;
    }

    /**
     * @param QueryStream $stream
     * @param ExecOptions $options
     *
     * @return ExecResultDto
     */
    public function executeQueryStream(QueryStream $stream, ExecOptions $options): ExecResultDto
    {
        $queries = $this->querySplitter->splitQueries($stream);
        return $this->withTimer(true)
            ->withLogger(true)
            ->executeQueries($queries, $options);
    }

    /**
     * @param QueryListDto $list
     * @param ExecOptions $options
     * @param SelectDqDto|null $select
     *
     * @return ExecResultDto
     */
    public function executeQueryList(QueryListDto $list,
        ExecOptions $options, SelectDqDto|null $select = null): ExecResultDto
    {
        if ($list->error !== null || count($list->queries ?? []) === 0) {
            $resultDto = new ExecResultDto();
            $resultDto->errors = 1;
            $resultDto->error = $list->error ?? $this->utils()
                ->lang('Cannot execute an empty query list.');
            return $resultDto;
        }

        return $this->withTimer($options->withTimer)
            ->withLogger($options->withLogger)
            ->executeQueries($list->queries, $options, $select);
    }
}
