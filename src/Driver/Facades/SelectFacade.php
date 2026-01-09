<?php

namespace Lagdo\DbAdmin\Db\Driver\Facades;

use Lagdo\DbAdmin\Db\UiData\Dql\SelectDto;
use Lagdo\DbAdmin\Db\UiData\Dql\SelectQuery;
use Lagdo\DbAdmin\Db\UiData\Dql\SelectResult;
use Lagdo\DbAdmin\Db\Service\TimerService;
use Exception;

use function array_keys;
use function count;

/**
 * Facade to table select functions
 */
class SelectFacade extends AbstractFacade
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
     * @param AbstractFacade $dbFacade
     * @param TimerService $timer
     */
    public function __construct(AbstractFacade $dbFacade, protected TimerService $timer)
    {
        parent::__construct($dbFacade);
    }

    /**
     * @return SelectQuery
     */
    private function query(): SelectQuery
    {
        return $this->selectQuery ??= new SelectQuery($this->page, $this->driver, $this->utils);
    }

    /**
     * @return SelectResult
     */
    private function result(): SelectResult
    {
        return $this->selectResult ??= new SelectResult($this->page, $this->driver, $this->utils);
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
        $tableStatus = $this->driver->tableStatusOrName($table);
        $tableName = $this->page->tableName($tableStatus);
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
            $query = $this->driver->getRowCountQuery($table, $selectDto->where,
                $hasGroupsInFields, $selectDto->group);
            return (int)$this->driver->result($query);
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
        $statement = $this->driver->execute($selectDto->query);
        $selectDto->duration = $this->timer->duration();
        $selectDto->rows = [];

        // From adminer.inc.php
        if (!$statement) {
            $selectDto->error = $this->driver->error();
            return;
        }

        // From select.inc.php
        $selectDto->rows = [];
        while (($row = $statement->fetchAssoc())) {
            if ($selectDto->page && $this->driver->jush() === "oracle") {
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
                'message' => $this->utils->trans->lang('No rows.'),
            ];
        }

        // $backward_keys = $this->driver->backwardKeys($table, $tableName);
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
