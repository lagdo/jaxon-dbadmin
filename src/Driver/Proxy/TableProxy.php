<?php

namespace Lagdo\DbAdmin\Support\Driver\Proxy;

use Lagdo\DbAdmin\Driver\Sql\Dto\TableDto;
use Lagdo\DbAdmin\Support\Driver\AbstractDriverProxy;
use Lagdo\DbAdmin\Support\Driver\UiDto\Ddl\ColumnFormDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\Ddl\ForeignKeyTrait;
use Lagdo\DbAdmin\Support\Driver\UiDto\Ddl\TableContent;
use Lagdo\DbAdmin\Support\Driver\UiDto\Ddl\TableFormDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\Ddl\TableHeader;
use Lagdo\Facades\Logger;
use Exception;

/**
 * Proxy to table functions
 */
class TableProxy extends AbstractDriverProxy
{
    use ForeignKeyTrait;

    /**
     * The current table status
     *
     * @var mixed
     */
    protected $tableStatus = null;

    /**
     * @var TableHeader|null
     */
    private TableHeader|null $tableHeader = null;

    /**
     * @var TableContent|null
     */
    private TableContent|null $tableContent = null;

    /**
     * @return TableHeader
     */
    private function header(): TableHeader
    {
        return $this->tableHeader ??= new TableHeader($this);
    }

    /**
     * @return TableContent
     */
    private function content(): TableContent
    {
        return $this->tableContent ??= new TableContent($this);
    }

    /**
     * Get the current table status
     *
     * @param string $table
     *
     * @return mixed
     */
    protected function status(string $table)
    {
        return $this->tableStatus ??= $this->engine()->tableStatusOrName($table, true);
    }

    /**
     * Get details about a table
     *
     * @param string $table
     *
     * @return array
     */
    public function getTableInfo(string $table): array
    {
        // From table.inc.php
        $status = $this->status($table);

        return $this->header()->infos($table, $status);
    }

    /**
     * Get the columns of a table
     *
     * @param string $table
     *
     * @return array
     * @throws Exception
     */
    public function getTableColumns(string $table): array
    {
        // From table.inc.php
        $columns = $this->engine()->columns($table);
        if (empty($columns)) {
            Logger::warning('Unable to get columns from table.', [
                'table' => $table,
                'schema' => $this->db()->schema,
                'database' => $this->db()->name,
                'server' => $this->db()->server,
            ]);
            throw new Exception($this->engine()->error());
        }

        $status = $this->status($table);

        return [
            'headers' => $this->header()->columns(),
            'details' => $this->content()->columns($columns, $status?->collation ?? ''),
        ];
    }

    /**
     * Get the indexes of a table
     *
     * @param string $table
     *
     * @return array|null
     */
    public function getTableIndexes(string $table): ?array
    {
        if (!$this->engine()->support('indexes')) {
            return null;
        }

        // From table.inc.php
        $indexes = $this->engine()->indexes($table);

        return [
            'headers' => $this->header()->indexes(),
            'details' => $this->content()->indexes($indexes),
        ];
    }

    /**
     * Get the foreign keys of a table
     *
     * @param string $table
     *
     * @return array|null
     */
    public function getTableForeignKeys(string $table): ?array
    {
        $status = $this->status($table);
        if (!$this->engine()->supportForeignKeys($status)) {
            return null;
        }

        $foreignKeys = $this->engine()->foreignKeys($table);

        return [
            'headers' => $this->header()->foreignKeys(),
            'details' => $this->content()->foreignKeys($foreignKeys),
        ];
    }

    /**
     * Get the triggers of a table
     *
     * @param string $table
     *
     * @return array|null
     */
    public function getTableTriggers(string $table): ?array
    {
        if (!$this->engine()->support('trigger')) {
            return null;
        }

        // From table.inc.php
        $triggers = $this->engine()->triggers($table);

        return [
            'headers' => $this->header()->triggers(),
            'details' => $this->content()->triggers($triggers),
        ];
    }

    /**
     * @param string $table
     *
     * @return TableDto
     * @throws Exception
     */
    private function getTableStatus(string $table): TableDto
    {
        if ($table === '') {
            return new TableDto($table, fn() => []);
        }

        $status = $this->engine()->tableStatus($table);
        if (!$status) {
            throw new Exception($this->utils()->lang("No table with name $table."));
        }
        return $status;
    }

    /**
     * Get required data for create/update on tables
     *
     * @param string $table
     *
     * @return array
     * @throws Exception
     */
    public function getTableMetadata(string $table = ''): array
    {
        // From create.inc.php
        $status = $this->getTableStatus($table);
        $foreignKeys = $this->getForeignKeys($table);

        return $this->content()->metadata($status, $foreignKeys);
    }

    /**
     * Get a new table column
     *
     * @param array|null $values
     *
     * @return ColumnFormDto
     */
    public function newColumnInput(array|null $values = null): ColumnFormDto
    {
        $foreignKeys = $this->getForeignKeys();
        return $this->content()->newColumnInput($values, $foreignKeys);
    }

    /**
     * @param ColumnFormDto $input
     *
     * @return ColumnFormDto
     */
    public function setInputFieldProperties(ColumnFormDto $input): ColumnFormDto
    {
        $foreignKeys = $this->getForeignKeys();
        return $this->content()->setInputFieldProperties($input, $foreignKeys);
    }

    /**
     * Get SQL command to create a table
     *
     * @param TableFormDto $table
     *
     * @return array
     */
    public function getCreateTableQueries(TableFormDto $table): array
    {
        $createDto = $this->content()->makeCreateDto($table);

        return $createDto->error !== null ? [
            'error' => $createDto->error,
        ] : [
            'queries' => $this->statement()->getCreateTableQueries($createDto),
        ];
    }

    /**
     * Create a table
     *
     * @param TableFormDto $table
     *
     * @return array
     */
    public function createTable(TableFormDto $table): array
    {
        $queries = $this->getCreateTableQueries($table);

        return compact('success', 'error', 'message');
    }

    /**
     * Get SQL command to alter a table
     *
     * @param TableFormDto $table
     *
     * @return array
     */
    public function getAlterTableQueries(TableFormDto $table): array
    {
        $alterDto = $this->content()->makeAlterDto($table);

        return $alterDto->error !== null ? [
            'error' => $alterDto->error,
        ] : [
            'queries' => $this->statement()->getAlterTableQueries($alterDto),
        ];
    }

    /**
     * Alter a table
     *
     * @param TableFormDto $table
     *
     * @return array
     * @throws Exception
     */
    public function alterTable(TableFormDto $table): array
    {
        $queries = $this->getAlterTableQueries($table);

        return compact('success', 'error', 'message');
    }

    /**
     * Drop a table
     *
     * @param string $table
     *
     * @return array
     */
    public function dropTable(string $table): array
    {
        return match(true) {
            $this->engine()->tableStatus($table) === null => [
                'error' => $this->utils()->lang('Invalid table %s.', $table),
            ],
            !$this->engine()->dropTables([$table]) => [
                'error' => $this->utils()->lang('Invalid table %s.', $table),
            ],
            default => [
                'message' => $this->utils()->lang('Table has been dropped.'),
            ],
        };
    }
}
