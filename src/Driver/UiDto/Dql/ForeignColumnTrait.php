<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto\Dql;

use Jaxon\Config\Config;
use Lagdo\DbAdmin\Driver\EngineInterface;
use Lagdo\DbAdmin\Driver\Sql\Dto\ColumnDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\ForeignKeyDto;
use Lagdo\DbAdmin\Driver\StatementInterface;
use Lagdo\DbAdmin\Support\Driver\CurrentDbDto;

use function is_array;
use function preg_match;

trait ForeignColumnTrait
{
    /**
     * @return CurrentDbDto
     */
    abstract protected function currentDb(): CurrentDbDto;

    /**
     * @return Config
     */
    abstract protected function config(): Config;

    /**
     * @return EngineInterface
     */
    abstract protected function engine(): EngineInterface;

    /**
     * @return StatementInterface
     */
    abstract protected function statement(): StatementInterface;

    /**
     * @param string $configKey
     *
     * @return bool
     */
    private function hasForeignColumnOption(string $configKey): bool
    {
        // The "select" key is mandatory.
        return $this->config()->hasOption("$configKey.select");
    }

    /**
     * @param ForeignKeyDto $foreignKey
     *
     * @return array|null
     */
    private function getForeignColumnOptions(ForeignKeyDto $foreignKey): array|null
    {
        $server = $this->currentDb()->server;
        $database = $foreignKey->database ?: $this->currentDb()->name;
        $schema = $foreignKey->schema ?: $this->currentDb()->schema;
        $table = $foreignKey->table;
        $idColumn = $foreignKey->target[0];
        if (!$this->engine()->support('scheme')) {
            $configKey = "foreigns.$server.$database.$table.$idColumn";
            return $this->hasForeignColumnOption($configKey) ?
                $this->config()->getOption($configKey) : null;
        }

        $configKey = "foreigns.$server.$database.$schema.$table.$idColumn";
        if ($this->hasForeignColumnOption($configKey)) {
            return $this->config()->getOption($configKey);
        }

        $configKey = "foreigns.$server.$database.*.$table.$idColumn";
        return $this->hasForeignColumnOption($configKey) ?
            $this->config()->getOption($configKey) : null;
    }

    /**
     * Check if the id column exists and is readable.
     *
     * @param string $idColumn
     * @param array<ColumnDto> $columns
     *
     * @return bool
     */
    private function foreignIdColumnIsValid(string $idColumn, array $columns): bool
    {
        foreach ($columns as $column) {
            if ($column->name === $idColumn) {
                return isset($column->privileges["select"]);
            }
        }
        return false;
    }

    /**
     * Check if a column is searchable.
     *
     * @param ColumnDto $column
     *
     * @return bool
     */
    private function foreignColumnIsSearchable(ColumnDto $column): bool
    {
        // Take the first varchar or text column.
        // if (preg_match("~varchar|character varying~", $column->type)) {
        return !$column->primary &&
            isset($column->privileges["select"]) &&
            preg_match("~char|text~", $column->type);
    }

    /**
     * @param ForeignKeyDto $foreignKey
     *
     * @return ForeignColumnDto|null
     */
    public function getForeignKeyColumn(ForeignKeyDto $foreignKey): ForeignColumnDto|null
    {
        $idColumn = $foreignKey->target[0];
        $columns = $this->engine()->columns($foreignKey->table);
        if (!$this->foreignIdColumnIsValid($idColumn, $columns)) {
            return null;
        }

        $config = $this->getForeignColumnOptions($foreignKey);
        if (is_array($config)) {
            $select = $config['select'];
            $search = $config['search'] ?? null;
            $joins = $config['joins'] ?? [];
            return new ForeignColumnDto($foreignKey, $idColumn, $select, $search, $joins);
        }

        foreach ($columns as $column) {
            if ($this->foreignColumnIsSearchable($column)) {
                $columnName = $this->statement()->escapeId($column->name);
                $select = fn(int $textLength) => "SUBSTR($columnName, 1, $textLength)";
                $search = fn(string $search) => $this->engine()->pgsql() ?
                    "$columnName ILIKE $search" : "LOWER($columnName) LIKE $search";
                return new ForeignColumnDto($foreignKey, $idColumn, $select, $search);
            }
        }

        return null;
    }
}
