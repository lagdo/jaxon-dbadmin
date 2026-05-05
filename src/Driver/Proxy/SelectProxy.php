<?php

namespace Lagdo\DbAdmin\Support\Driver\Proxy;

use Lagdo\DbAdmin\Support\Driver\AbstractDriverProxy;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dql\SelectDqDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dql\SelectQuery;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dql\SelectResult;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dql\SelectTableDto;
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
     * @param array $queryParams The user params
     *
     * @return SelectDqDto
     * @throws Exception
     */
    private function prepareSelect(string $table, array $queryParams = []): SelectDqDto
    {
        $table = new SelectTableDto($table);
        $table->status = $this->engine()->tableStatusOrName($table->name);
        $table->columns = $this->engine()->columns($table->name);
        $table->indexes = $this->engine()->indexes($table->name);
        $table->foreignKeys = $this->engine()->foreignKeys($table->name);

        $input = new SelectDqDto($table, $queryParams);
        return $this->query()->prepareSelect($input);
    }

    /**
     * Get required data for create/update on tables
     *
     * @param string $table The table name
     * @param array $queryParams The user params
     *
     * @return SelectDqDto
     * @throws Exception
     */
    public function getSelectParams(string $table, array $queryParams = []): SelectDqDto
    {
        return $this->prepareSelect($table, $queryParams);
    }

    /**
     * Get required data for create/update on tables
     *
     * @param string $table The table name
     * @param array $queryParams The user params
     *
     * @return int
     */
    public function countSelect(string $table, array $queryParams): int
    {
        $input = $this->prepareSelect($table, $queryParams);
        $hasGroupsInColumns = count($input->groups) < count($input->columns);

        try {
            $query = $this->statement()->getRowCountQuery($table, $input->wheres,
                $hasGroupsInColumns, $input->groups);
            return (int)$this->engine()->columnValue($query);
        } catch(Exception) {
            return -1;
        }
    }

    /**
     * @param SelectDqDto $input
     *
     * @return void
     */
    private function executeQuery(SelectDqDto $input): void
    {
        $this->timer->start();

        // From driver.inc.php
        $result = $this->engine()->executeQuery($input->query);
        $input->duration = $this->timer->duration();
        $input->rows = [];

        // From adminer.inc.php
        if ($result->hasError()) {
            $input->error = $this->engine()->error();
            return;
        }

        // From select.inc.php
        $input->rows = [];
        while (($row = $result->fetchAssoc())) {
            if ($input->page && $this->engine()->oracle()) {
                unset($row["RNUM"]);
            }
            $input->rows[] = $row;
        }
    }

    /**
     * Get required data for create/update on tables
     *
     * @param string $table The table name
     * @param array $queryParams The user params
     *
     * @return array
     * @throws Exception
     */
    public function execSelect(string $table, array $queryParams): array
    {
        $input = $this->prepareSelect($table, $queryParams);
        $this->executeQuery($input);

        if ($input->error !== null) {
            return [
                'message' => $input->error,
            ];
        }
        if (count($input->rows) === 0) {
            return [
                'message' => $this->utils()->lang('No rows.'),
            ];
        }

        // $backward_keys = $this->engine()->backwardKeys($table, $tableName);
        // lengths = $this->getValuesLengths($rows, $input->queryOptions);

        $queryColumns = array_keys($input->rows[0]);
        $this->result()->setResultHeaders($input, $queryColumns);

        return [
            'headers' => $input->headers,
            'query' => $input->query,
            'limit' => $input->limit,
            'duration' => $input->duration,
            'rows' => $this->result()->getRows($input),
        ];
    }
}
