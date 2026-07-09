<?php

namespace Lagdo\DbAdmin\Support\Driver\Proxy;

use Jaxon\Config\Config;
use Lagdo\DbAdmin\Driver\Sql\Dto\ForeignKeyDto;
use Lagdo\DbAdmin\Support\Driver\AbstractDriverProxy;
use Lagdo\DbAdmin\Support\Driver\UiDto\ForeignColumnTrait;
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
use function array_values;
use function count;

/**
 * Proxy to table select functions
 */
class SelectProxy extends AbstractDriverProxy
{
    use ForeignColumnTrait;

    /**
     * @var Config
     */
    private Config $packageConfig;

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
     * @return Config
     */
    protected function config(): Config
    {
        return $this->packageConfig;
    }

    /**
     * @param Config $packageConfig
     *
     * @return static
     */
    public function setPackageConfig(Config $packageConfig): static
    {
        $this->packageConfig = $packageConfig;
        return $this;
    }

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
        return $this->query()->prepareSelect($select);
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
     * @param bool $withTimer
     *
     * @return QueryResultDto<SelectRowsetDto>
     * @throws Exception
     */
    public function execSelect(SelectDqDto $select, bool $withTimer = true): QueryResultDto
    {
        $options = new QueryOptions();
        $options->setExecOptions(false, false, true);
        $options->withTimer = $withTimer;

        $queryList = new QueryListDto(queries: [$select->query()]);

        return $this->processor->executeQueryList($queryList, $options, $select);
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

        [$idColumn, $select, $filter] = $this->getForeignKeyColumn($foreignKey);
        if ($idColumn === '') {
            return null;
        }

        $rowset = new ForeignRowsetDto($foreignKey);
        $rowset->idColumn = $idColumn;
        $rowset->select = $select;
        $rowset->filter = $filter;
        return $rowset;
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
        $labelColumn = ($rowset->select)($textLength);
        $idValues = implode(', ', array_map($this->engine()->quote(...), $rowset->values));

        return "SELECT $idColumn as id, $labelColumn as label
FROM $tableName WHERE $idColumn IN ($idValues)";
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

        $filterRowset = fn(array $column) => $column['foreign'] !== null &&
            $column['value'] !== null && isset($foreignRowsets[$column['foreign']->table]);

        // Get the referenced ids in the foreign tables.
        foreach ($rowset->rows as $row) {
            foreach (array_filter($row->columns, $filterRowset) as $column) {
                $foreignRowsets[$column['foreign']->table]
                    ->values[$column['value']] = $column['value'];
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

        $setForeignValues = fn(ForeignRowsetDto $foreign, QueryRowsetDto $query) =>
            $foreign->values = array_combine(
                array_map(fn(array $row) => $row[0], $query->rows),
                array_map(fn(array $row) => $row[1], $query->rows)
            );
        array_map($setForeignValues, $foreignRowsets, $result->rowsets);

        foreach ($rowset->rows as $row) {
            foreach ($row->columns as &$column) {
                if ($filterRowset($column)) {
                    $table = $foreignRowsets[$column['foreign']->table] ?? null;
                    $column['foreignLabel'] = $table?->values[$column['value']] ?? null;
                }
            }
        }

        return $foreignRowsetCount;
    }

    /**
     * @param string $message
     *
     * @return QueryResultDto
     */
    private function errorResult(string $message): QueryResultDto
    {
        $resultDto = new QueryResultDto();
        $resultDto->errors = 1;
        $resultDto->error = $message;
        return $resultDto;
    }

    /**
     * Search in the column referenced by a foreign key.
     *
     * @param string $table
     * @param string $column
     * @param string $search
     * @param array $queryParams
     *
     * @return QueryResultDto<SelectRowsetDto>
     */
    public function searchInForeignColumn(string $table, string $column,
        string $search, array $queryParams): QueryResultDto
    {
        $foreignKeys = array_values(array_filter($this->engine()->foreignKeys($table),
            fn(ForeignKeyDto $foreignKey) => count($foreignKey->source) === 1 &&
                $foreignKey->source[0] === $column));
        if (($foreignKey = $foreignKeys[0] ?? null) === null) {
            return $this->errorResult($this->utils()->lang('Unable to find the foreign key.'));
        }
        if (($rowset = $this->getForeignTableRowset($foreignKey)) === null) {
            return $this->errorResult($this->utils()->lang('Cannot select the foreign column.'));
        }

        $queryParams = [
            ...$queryParams,
            'columns' => [[
                'func' => '',
                'column' => $rowset->idColumn,
            ]],
            'filters' => [],
            'sorters' => [[
                'desc' => false,
                'column' => $rowset->idColumn,
            ]],
            'foreigns' => false,
        ];
        $foreignTable = new SelectTableDto($foreignKey->table);
        $foreignTable->status = $this->engine()->tableStatusOrName($foreignTable->name);
        $foreignTable->columns = $foreignTable->status->columns();
        $foreignTable->indexes = $this->engine()->indexes($foreignTable->name);
        $foreignTable->foreignKeys = []; // No need to have foreign keys here.

        $select = $this->options()->createSelectDto($foreignTable, $queryParams);
        $select = $this->query()->prepareSelect($select);

        // Set the search clauses.
        $select->columns[] = ($rowset->select)($queryParams['length']);
        $select->filters[] = ($rowset->filter)($this->engine()->quote("%{$search}%"));

        return $this->execSelect($select);
    }
}
