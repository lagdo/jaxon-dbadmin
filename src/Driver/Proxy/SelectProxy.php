<?php

namespace Lagdo\DbAdmin\Support\Driver\Proxy;

use Lagdo\DbAdmin\Support\Driver\AbstractDriverProxy;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dql\SelectDqDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dql\SelectOptions;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dql\SelectQuery;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dql\SelectTableDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\ExecOptions;
use Lagdo\DbAdmin\Support\Driver\UiDto\ExecResultDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\QueryListDto;
use Exception;

/**
 * Proxy to table select functions
 */
class SelectProxy extends AbstractDriverProxy
{
    /**
     * @var SelectOptions
     */
    private SelectOptions $selectOptions;

    /**
     * @var SelectQuery
     */
    private SelectQuery $selectQuery;

    /**
     * @var QueryProcessor
     */
    private QueryProcessor $processor;

    /**
     * @return SelectOptions
     */
    private function options(): SelectOptions
    {
        return $this->selectOptions ??= new SelectOptions($this);
    }

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
     * @param array $queryParams The user inputs
     *
     * @return SelectDqDto
     * @throws Exception
     */
    private function prepareSelect(string $table, array $queryParams): SelectDqDto
    {
        $table = new SelectTableDto($table);
        $table->status = $this->engine()->tableStatusOrName($table->name);
        $table->columns = $table->status->columns();
        $table->indexes = $this->engine()->indexes($table->name);
        $table->foreignKeys = $this->engine()->foreignKeys($table->name);

        $select = $this->options()->createSelectDto($table, $queryParams);
        return $this->query()->prepareSelect($select);
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
     * @param SelectDqDto $select
     *
     * @return int
     */
    public function countSelect(SelectDqDto $select): int
    {
        try {
            $query = $this->statement()->getRowCountQuery($select->table->name,
                $select->filters, $select->grouped, $select->groupBy);
            return (int)$this->engine()->columnValue($query);
        } catch(Exception) {
            return -1;
        }
    }

    /**
     * Get required data for create/update on tables
     *
     * @param SelectDqDto $select
     *
     * @return ExecResultDto
     * @throws Exception
     */
    public function execSelect(SelectDqDto $select): ExecResultDto
    {
        $options = new ExecOptions();
        $options->setExecOptions(false, false, true);
        $options->withTimer = true;

        $queryList = new QueryListDto(queries: [$select->query]);

        return $this->processor->executeQueryList($queryList, $options, $select);
    }
}
