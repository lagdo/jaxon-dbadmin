<?php

namespace Lagdo\DbAdmin\Support\Driver\Proxy;

use Lagdo\DbAdmin\Support\Driver\AbstractDriverProxy;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dql\SelectDqDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dql\SelectQuery;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dql\SelectTableDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\ExecOptions;
use Lagdo\DbAdmin\Support\Driver\UiDto\ExecResultDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\QueryListDto;
use Exception;

use function count;

/**
 * Proxy to table select functions
 */
class SelectProxy extends AbstractDriverProxy
{
    /**
     * @var SelectQuery
     */
    private SelectQuery $selectQuery;

    /**
     * @var QueryProcessor
     */
    private QueryProcessor $processor;

    /**
     * @return SelectQuery
     */
    private function query(): SelectQuery
    {
        return $this->selectQuery ??= new SelectQuery($this);
    }

    /**
     * @param QueryProcessor $processor
     *
     * @return static
     */
    public function setProcessor(QueryProcessor $processor): static
    {
        $this->processor = $processor;
        return $this;
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
        $table->columns = $table->status->columns();
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
     * Get required data for create/update on tables
     *
     * @param string $table The table name
     * @param array $queryParams The user params
     *
     * @return ExecResultDto
     * @throws Exception
     */
    public function execSelect(string $table, array $queryParams): ExecResultDto
    {
        $options = new ExecOptions();
        $options->select = $this->prepareSelect($table, $queryParams);
        $options->setExecOptions(false, false, true);

        $queryList = new QueryListDto(queries: [$options->select->query]);
        $queryList->withTimer = true;

        return $this->processor->executeQueryList($queryList, $options);
    }
}
