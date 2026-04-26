<?php

namespace Lagdo\DbAdmin\Support\Driver\Proxy;

use Lagdo\DbAdmin\Support\Driver\AbstractDriverProxy;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dql\SelectDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dql\SelectQuery;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dql\SelectResult;
use Lagdo\DbAdmin\Support\Service\TimerService;
use Exception;

use function array_keys;
use function count;

/**
 * Proxy to table select functions
 */
class SelectProxy extends AbstractDriverProxy
{
    /**
     * @var SelectQuery|null
     */
    private SelectQuery|null $selectQuery = null;

    /**
     * @var SelectResult|null
     */
    private SelectResult|null $selectResult = null;

    /**
     * @var TimerService
     */
    protected TimerService $timer;

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
     * @return SelectQuery
     */
    private function query(): SelectQuery
    {
        return $this->selectQuery ??= new SelectQuery($this);
    }

    /**
     * @return SelectResult
     */
    private function result(): SelectResult
    {
        return $this->selectResult ??= new SelectResult($this);
    }

    /**
     * @param string $table The table name
     * @param array $queryOptions The query options
     *
     * @return SelectDto
     * @throws Exception
     */
    private function prepareSelect(string $table, array $queryOptions = []): SelectDto
    {
        $tableStatus = $this->engine()->tableStatusOrName($table);
        $tableName = $this->pageUi()->tableName($tableStatus);
        $selectDto = new SelectDto($table, $tableName,
            $tableStatus, $queryOptions);
        return $this->query()->prepareSelect($selectDto);
    }

    /**
     * Get required data for create/update on tables
     *
     * @param string $table The table name
     * @param array $queryOptions The query options
     *
     * @return SelectDto
     * @throws Exception
     */
    public function getSelectData(string $table, array $queryOptions = []): SelectDto
    {
        return $this->prepareSelect($table, $queryOptions);
    }

    /**
     * Get required data for create/update on tables
     *
     * @param string $table The table name
     * @param array $queryOptions The query options
     *
     * @return int
     */
    public function countSelect(string $table, array $queryOptions): int
    {
        $selectDto = $this->prepareSelect($table, $queryOptions);
        $hasGroupsInFields = count($selectDto->group) < count($selectDto->select);

        try {
            $query = $this->statement()->getRowCountQuery($table, $selectDto->where,
                $hasGroupsInFields, $selectDto->group);
            return (int)$this->engine()->result($query);
        } catch(Exception) {
            return -1;
        }
    }

    /**
     * @param SelectDto $selectDto
     *
     * @return void
     */
    private function executeQuery(SelectDto $selectDto): void
    {
        $this->timer->start();

        // From driver.inc.php
        $statement = $this->engine()->execute($selectDto->query);
        $selectDto->duration = $this->timer->duration();
        $selectDto->rows = [];

        // From adminer.inc.php
        if (!$statement) {
            $selectDto->error = $this->engine()->error();
            return;
        }

        // From select.inc.php
        $selectDto->rows = [];
        while (($row = $statement->fetchAssoc())) {
            if ($selectDto->page && $this->engine()->oracle()) {
                unset($row["RNUM"]);
            }
            $selectDto->rows[] = $row;
        }
    }

    /**
     * Get required data for create/update on tables
     *
     * @param string $table The table name
     * @param array $queryOptions The query options
     *
     * @return array
     * @throws Exception
     */
    public function execSelect(string $table, array $queryOptions): array
    {
        $selectDto = $this->prepareSelect($table, $queryOptions);
        $this->executeQuery($selectDto);

        if ($selectDto->error !== null) {
            return [
                'message' => $selectDto->error,
            ];
        }
        if (count($selectDto->rows) === 0) {
            return [
                'message' => $this->utils()->lang('No rows.'),
            ];
        }

        // $backward_keys = $this->engine()->backwardKeys($table, $tableName);
        // lengths = $this->getValuesLengths($rows, $selectDto->queryOptions);

        $queryFields = array_keys($selectDto->rows[0]);
        $this->result()->setResultHeaders($selectDto, $queryFields);

        return [
            'headers' => $selectDto->headers,
            'query' => $selectDto->query,
            'limit' => $selectDto->limit,
            'duration' => $selectDto->duration,
            'rows' => $this->result()->getRows($selectDto),
        ];
    }
}
