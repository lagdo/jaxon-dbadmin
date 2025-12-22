<?php

namespace Lagdo\DbAdmin\Db\Driver\Facades;

use Lagdo\DbAdmin\Db\Page\Dql\SelectEntity;
use Lagdo\DbAdmin\Db\Page\Dql\SelectQuery;
use Lagdo\DbAdmin\Db\Page\Dql\SelectResult;
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
     * @return SelectEntity
     * @throws Exception
     */
    private function prepareSelect(string $table, array $queryOptions = []): SelectEntity
    {
        $tableStatus = $this->driver->tableStatusOrName($table);
        $tableName = $this->page->tableName($tableStatus);
        $selectEntity = new SelectEntity($table, $tableName,
            $tableStatus, $queryOptions);
        return $this->query()->prepareSelect($selectEntity);
    }

    /**
     * Get required data for create/update on tables
     *
     * @param string $table The table name
     * @param array $queryOptions The query options
     *
     * @return SelectEntity
     * @throws Exception
     */
    public function getSelectData(string $table, array $queryOptions = []): SelectEntity
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
        $selectEntity = $this->prepareSelect($table, $queryOptions);
        $hasGroupsInFields = count($selectEntity->group) < count($selectEntity->select);

        try {
            $query = $this->driver->getRowCountQuery($table, $selectEntity->where,
                $hasGroupsInFields, $selectEntity->group);
            return (int)$this->driver->result($query);
        } catch(Exception) {
            return -1;
        }
    }

    /**
     * @param SelectEntity $selectEntity
     *
     * @return void
     */
    private function executeQuery(SelectEntity $selectEntity): void
    {
        $this->timer->start();

        // From driver.inc.php
        $statement = $this->driver->execute($selectEntity->query);
        $selectEntity->duration = $this->timer->duration();
        $selectEntity->rows = [];

        // From adminer.inc.php
        if (!$statement) {
            $selectEntity->error = $this->driver->error();
            return;
        }

        // From select.inc.php
        $selectEntity->rows = [];
        while (($row = $statement->fetchAssoc())) {
            if ($selectEntity->page && $this->driver->jush() === "oracle") {
                unset($row["RNUM"]);
            }
            $selectEntity->rows[] = $row;
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
        $selectEntity = $this->prepareSelect($table, $queryOptions);
        $this->executeQuery($selectEntity);

        if ($selectEntity->error !== null) {
            return [
                'message' => $selectEntity->error,
            ];
        }
        if (count($selectEntity->rows) === 0) {
            return [
                'message' => $this->utils->trans->lang('No rows.'),
            ];
        }

        // $backward_keys = $this->driver->backwardKeys($table, $tableName);
        // lengths = $this->getValuesLengths($rows, $selectEntity->queryOptions);

        $queryFields = array_keys($selectEntity->rows[0]);
        $this->result()->setResultHeaders($selectEntity, $queryFields);

        return [
            'headers' => $selectEntity->headers,
            'query' => $selectEntity->query,
            'limit' => $selectEntity->limit,
            'duration' => $selectEntity->duration,
            'rows' => $this->result()->getRows($selectEntity),
        ];
    }
}
