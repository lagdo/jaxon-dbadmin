<?php

namespace Lagdo\DbAdmin\Support\Driver\Proxy;

use Lagdo\DbAdmin\Driver\Sql\Dto\ForeignKeyDto;
use Lagdo\DbAdmin\Support\Driver\AbstractDriverProxy;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dql\ForeignRowsetDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dql\QueryResultHeaderDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dql\QueryRowsetDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dql\SelectDqDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dql\SelectOptions;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dql\SelectQuery;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dql\SelectRowsetDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dql\SelectTableDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\QueryListDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\QueryOptions;
use Lagdo\DbAdmin\Support\Driver\UiDto\QueryResultDto;
use Exception;

use function array_combine;
use function array_filter;
use function array_map;
use function count;

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
        $table = new SelectTableDto($table);
        $table->status = $this->engine()->tableStatusOrName($table->name);
        $table->columns = $table->status->columns();
        $table->indexes = $this->engine()->indexes($table->name);
        $table->foreignKeys = $this->engine()->foreignKeys($table->name);

        $select = $this->options()->createSelectDto($table, $queryParams);
        $select = $this->query()->prepareSelect($select);

        // Fetching the changed data after a successful update.
        if (isset($queryParams['updated'])) {
            $select->filters[] = $this->engine()->where($queryParams['updated'], $table->columns);
        }

        return $this->query()->makeSelect($select);
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
     * @return QueryResultDto<SelectRowsetDto>
     * @throws Exception
     */
    public function execSelect(SelectDqDto $select): QueryResultDto
    {
        $options = new QueryOptions();
        $options->setExecOptions(false, false, true);
        $options->withTimer = true;

        $queryList = new QueryListDto(queries: [$select->query]);

        return $this->processor->executeQueryList($queryList, $options, $select);
    }

    /**
     * @param string $table
     *
     * @return string
     */
    private function getDescriptionColumn(string $table): string
    {
        // Take the first varchar column.
        foreach ($this->engine()->columns($table) as $column) {
            if (preg_match("~varchar|character|text~", $column->type)) {
                return $column->name;
            }
        }
        return '';
    }

    /**
     * @param ForeignKeyDto $foreignKey
     *
     * @return ForeignRowsetDto|null
     */
    private function getForeignTableRowset(ForeignKeyDto $foreignKey): ForeignRowsetDto|null
    {
        if (count($foreignKey->target) !== 1) {
            return null;
        }

        $foreignRows = new ForeignRowsetDto($foreignKey);
        $foreignRows->labelColumn = $this->getDescriptionColumn($foreignKey->table);
        return $foreignRows->labelColumn === '' ? null : $foreignRows;
    }

    /**
     * @param ForeignRowsetDto $rowset
     * @param int $textLength
     *
     * @return string
     */
    private function getForeignRowsetQuery(ForeignRowsetDto $rowset, int $textLength): string
    {
        $tableName = $this->statement()->escapeId($rowset->fkey->table);
        $idColumn = $this->statement()->escapeId($rowset->fkey->target[0]);
        $labelColumn = $this->statement()->escapeId($rowset->labelColumn);
        $idValues = implode(', ', array_map($this->engine()->quote(...), $rowset->values));

        return "SELECT DISTINCT $idColumn as id,
SUBSTR($labelColumn, 1, $textLength) as label
FROM $tableName WHERE $idColumn IN ($idValues)";
    }

    /**
     * @param ForeignRowsetDto $foreign
     * @param QueryRowsetDto $query
     *
     * @return ForeignRowsetDto
     */
    private function setForeignValues(ForeignRowsetDto $foreign, QueryRowsetDto $query): ForeignRowsetDto
    {
        foreach ($query->rows as $row) {
            $foreign->values[$row[0]] = $row[1];
        }
        return $foreign;
    }

    /**
     * @param SelectRowsetDto $rowset
     * @param int $textLength
     *
     * @return int
     */
    public function setForeignKeyLabels(SelectRowsetDto $rowset, int $textLength): int
    {
        $notNull = fn($item) => $item !== null;
        $foreignKeyGetter = fn(QueryResultHeaderDto $header) => $header->foreignKey;
        $foreignKeys = array_filter(array_map($foreignKeyGetter, $rowset->headers), $notNull);
        if (count($foreignKeys) === 0) {
            return 0;
        }

        $foreignRowsetGetter = $this->getForeignTableRowset(...);
        $foreignRowsets = array_filter(array_map($foreignRowsetGetter, $foreignKeys), $notNull);
        // Key by table names
        $tableNameGetter = fn(ForeignRowsetDto $rowset) => $rowset->fkey->table;
        $tableNames = array_map($tableNameGetter, $foreignRowsets);
        $foreignRowsets = array_combine($tableNames, $foreignRowsets);
        $foreignRowsetCount = count($foreignRowsets);
        if ($foreignRowsetCount === 0) {
            return 0;
        }

        // Get the referenced ids in the foreign tables.
        foreach ($rowset->rows as $row) {
            foreach ($row->columns as $column) {
                if ($column['foreign'] !== null && $column['value'] !== null &&
                    isset($foreignRowsets[$column['foreign']->table])) {
                    $tableName = $column['foreign']->table;
                    $foreignRowsets[$tableName]->values[$column['value']] = $column['value'];
                }
            }
        }

        // Fetch the foreign tables data.
        $queryGetter = fn(ForeignRowsetDto $rowset) =>
            $this->getForeignRowsetQuery($rowset, $textLength);
        $queryList = new QueryListDto(queries: array_map($queryGetter, $foreignRowsets));
        $options = new QueryOptions();
        $options->setExecOptions(false, false, true);
        $options->withTimer = false;
        $result = $this->processor->executeQueryList($queryList, $options);

        array_map($this->setForeignValues(...), $foreignRowsets, $result->rowsets);

        foreach ($rowset->rows as $row) {
            foreach ($row->columns as &$column) {
                if ($column['foreign'] !== null && $column['value'] !== null &&
                    isset($foreignRowsets[$column['foreign']->table])) {
                    $table = $foreignRowsets[$column['foreign']->table] ?? null;
                    $column['foreignLabel'] = $table?->values[$column['value']];
                }
            }
        }

        return $foreignRowsetCount;
    }
}
