<?php

namespace Lagdo\DbAdmin\Support\Driver\Proxy;

use Jaxon\Config\Config;
use Lagdo\DbAdmin\Driver\Sql\Dto\ForeignKeyDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\SelectColumnDto;
use Lagdo\DbAdmin\Support\Driver\AbstractDriverProxy;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dql\ForeignColumnDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dql\ForeignColumnTrait;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dql\SelectDqDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dql\SelectOptions;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dql\SelectQuery;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dql\SelectRowsetDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\Dql\SelectTableDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\QueryListDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\QueryOptions;
use Lagdo\DbAdmin\Support\Driver\UiDto\QueryResultDto;
use Exception;

use function array_filter;
use function array_map;
use function array_values;
use function count;
use function implode;

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
     * @param string $table
     * @param array $queryParams
     *
     * @return SelectDqDto
     * @throws Exception
     */
    private function createSelectDto(string $table, array $queryParams): SelectDqDto
    {
        $table = new SelectTableDto($table);
        $table->status = $this->engine()->tableStatusOrName($table->name);
        $table->columns = $table->status->columns();
        $table->indexes = $this->engine()->indexes($table->name);
        $table->foreignKeys = [];

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
        $select = $this->createSelectDto($table, $queryParams);
        $select->table->foreignKeys = $this->engine()->foreignKeys($table);

        return $select;
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
     * @param ForeignColumnDto $query
     * @param string $tableName
     * @param int $textLength
     *
     * @return array
     */
    private function getForeignColumnQuery(ForeignColumnDto $query,
        string $tableName, int $textLength): array
    {
        $source = $query->fkey->source[0];
        $target = $query->fkey->target[0];
        $id = $this->statement()->escapeId($source);
        $targetTable = $this->statement()->escapeId($query->fkey->table);
        $targetId = "$targetTable." . $this->statement()->escapeId($target);
        $targetLabel = ($query->select)($textLength);
        $cteFrom = implode(' ', [$targetTable, ...$query->joins]);

        $cte = "{$source}_cte";
        $cteId = "_dbadmin_cte_{$source}_id";
        $cteLabel = "_dbadmin_cte_{$source}_label";
        $cteName = $this->statement()->escapeId($cte);
        $cteQuery = "SELECT $targetId as $cteId, $targetLabel as $cteLabel FROM $cteFrom";
        $cteId = $this->statement()->escapeId($cteId);
        $cteLabel = $this->statement()->escapeId($cteLabel);

        return [
            'cte' => "$cte AS ($cteQuery)",
            'label' => "$cteName.$cteLabel",
            'join' => "LEFT OUTER JOIN $cteName on $tableName.$id=$cteName.$cteId",
        ];
    }

    /**
     * @param SelectDqDto $select
     * @param ForeignKeyDto $foreignKey
     *
     * @return bool
     */
    private function foreignKeysIsSelectable(SelectDqDto $select, ForeignKeyDto $foreignKey): bool
    {
        if (count($foreignKey->source) !== 1) {
            return false;
        }
        // The source column must be selectable.
        if (!isset($select->selectableColumns[$foreignKey->source[0]])) {
            return false;
        }
        // In case of SELECT *.
        if (count($select->columns) === 0) {
            return true;
        }

        // The foreign key is among the selected columns.
        $filter = fn(SelectColumnDto $input) => $input->column?->name === $foreignKey->source[0];
        return count(array_filter($select->input->columns, $filter)) > 0;
    }

    /**
     * @param SelectDqDto $select
     *
     * @return string
     */
    private function getQueryWithCteClauses(SelectDqDto $select): string
    {
        $foreignKeys = $select->table->foreignKeys;
        $foreignKeys = array_filter($foreignKeys, fn(ForeignKeyDto $foreignKey) =>
                $this->foreignKeysIsSelectable($select, $foreignKey));
        $foreignColumns = array_map($this->getForeignKeyColumn(...), $foreignKeys);
        $foreignColumns = array_filter($foreignColumns, fn($item) => $item !== null);
        if (count($foreignColumns) === 0) {
            return $select->query();
        }

        $tableName = $this->statement()->escapeTableName($select->table->name);
        $queryGetter = fn(ForeignColumnDto $query) =>
            $this->getForeignColumnQuery($query, $tableName, $select->input->textLength);
        $queries = array_map($queryGetter, $foreignColumns);

        $columns = array_map(fn(array $query) => $query['label'], $queries);
        $select->cteColumns = array_values($columns);
        $select->joins = array_values(array_map(fn(array $query) => $query['join'], $queries));
        $ctes = array_values(array_map(fn(array $query) => $query['cte'], $queries));

        return 'WITH ' . implode(', ', $ctes) . ' ' . $select->query();
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

        $query = !$select->input->loadForeigns || count($select->table->foreignKeys) === 0 ?
            $select->query() : $this->getQueryWithCteClauses($select);
        $queryList = new QueryListDto(queries: [$query]);

        return $this->processor->executeQueryList($queryList, $options, $select);
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

        $foreignColumn = $this->getForeignKeyColumn($foreignKey);
        if ($foreignColumn === null || $foreignColumn->search === null) {
            return $this->errorResult($this->utils()->lang('Cannot search in the foreign column.'));
        }

        $queryParams = [
            ...$queryParams,
            'columns' => [],
            'filters' => [],
            'sorters' => [[
                'desc' => false,
                'column' => $foreignColumn->idColumn,
            ]],
            'foreigns' => false,
        ];
        $select = $this->createSelectDto($foreignKey->table, $queryParams);

        // Set the search clauses.
        $select->joins = $foreignColumn->joins;
        $select->columns = [
            $this->statement()->escapeId($foreignColumn->idColumn) . ' AS id',
            ($foreignColumn->select)($queryParams['length']) . ' AS label',
        ];
        $select->filters[] = ($foreignColumn->search)($this->engine()->quote("%{$search}%"));

        return $this->execSelect($select, false);
    }
}
