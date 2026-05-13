<?php

namespace Lagdo\DbAdmin\Support\Driver\Proxy;

use Lagdo\DbAdmin\Driver\Sql\Dto\TableAlterDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\TableCreateDto;
use Lagdo\DbAdmin\Support\Driver\AbstractDriverProxy;
use Lagdo\DbAdmin\Support\Driver\UiDto\Ddl\ColumnDdDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\Ddl\ForeignKeyTrait;
use Lagdo\DbAdmin\Support\Driver\UiDto\Ddl\TableAlter;
use Lagdo\DbAdmin\Support\Driver\UiDto\Ddl\TableContent;
use Lagdo\DbAdmin\Support\Driver\UiDto\Ddl\TableCreate;
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
     * @var TableCreate|null
     */
    private TableCreate|null $tableCreate = null;

    /**
     * @var TableAlter|null
     */
    private TableAlter|null $tableAlter = null;

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
     * @return TableCreate
     */
    private function create(): TableCreate
    {
        return $this->tableCreate ??= new TableCreate($this);
    }

    /**
     * @return TableAlter
     */
    private function alter(): TableAlter
    {
        return $this->tableAlter ??= new TableAlter($this);
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
        $status = null;
        $columns = [];
        if ($table !== '') {
            $status = $this->engine()->tableStatus($table);
            if (!$status) {
                throw new Exception($this->utils()->lang('No tables.'));
            }
            $columns = $status->columns();
        }

        $foreignKeys = $this->getForeignKeys($table);
        return $this->content()->metadata($status, $columns, $foreignKeys);
    }

    /**
     * Get a new table column
     *
     * @return ColumnDdDto
     */
    public function newColumnInput(): ColumnDdDto
    {
        $foreignKeys = $this->getForeignKeys();
        return $this->content()->newColumnInput($foreignKeys);
    }

    /**
     * @param ColumnDdDto $input
     *
     * @return ColumnDdDto
     */
    public function setInputFieldProperties(ColumnDdDto $input): ColumnDdDto
    {
        $foreignKeys = $this->getForeignKeys();
        return $this->content()->setInputFieldProperties($input, $foreignKeys);
    }

    /**
     * @param array $tableInputs
     *
     * @return TableCreateDto
     */
    public function getCreateTableDto(array $tableInputs): TableCreateDto
    {
        return new TableCreateDto($tableInputs, $this->getReferencableColumns(...));
    }

    /**
     * Get SQL command to create a table
     *
     * @param array $tableInputs
     * @param array<ColumnDdDto> $columnsInputs
     *
     * @return array
     */
    public function getCreateTableQueries(array $tableInputs, array $columnsInputs): array
    {
        $createDto = $this->getCreateTableDto($tableInputs);
        $createDto = $this->create()->makeDto($createDto, $columnsInputs);

        return $createDto->error !== null ? [
            'error' => $createDto->error,
        ] : [
            'queries' => $this->statement()->getCreateTableQueries($createDto),
        ];
    }

    /**
     * Create a table
     *
     * @param array $tableInputs
     * @param array<ColumnDdDto> $columnsInputs
     *
     * @return array
     */
    public function createTable(array $tableInputs, array $columnsInputs): array
    {
        $queries = $this->getCreateTableQueries($tableInputs, $columnsInputs);

        return compact('success', 'error', 'message');
    }

    /**
     * @param array $tableInputs
     *
     * @return TableAlterDto
     */
    public function getAlterTableDto(array $tableInputs): TableAlterDto
    {
        return new TableAlterDto($tableInputs, $this->getReferencableColumns(...));
    }

    /**
     * Get SQL command to alter a table
     *
     * @param string $table
     * @param array $tableInputs
     * @param array<ColumnDdDto> $columnsInputs
     *
     * @return array
     */
    public function getAlterTableQueries(string $table, array $tableInputs, array $columnsInputs): array
    {
        $alterDto = $this->getAlterTableDto($tableInputs);
        if (($status = $this->engine()->tableStatus($table)) === null) {
            return [
                'error' => $this->utils()->lang('Unable to find the table.'),
            ];
        }

        $alterDto->status = $status;
        $alterDto = $this->alter()->makeDto($alterDto, $columnsInputs);
        return $alterDto->error !== null ? ['error' => $alterDto->error] : [
            'queries' => $this->statement()->getAlterTableQueries($alterDto),
        ];
    }

    /**
     * Alter a table
     *
     * @param string $table
     * @param array $tableInputs
     * @param array<ColumnDdDto> $columnsInputs
     *
     * @return array
     * @throws Exception
     */
    public function alterTable(string $table, array $tableInputs, array $columnsInputs): array
    {
        $queries = $this->getAlterTableQueries($table, $tableInputs, $columnsInputs);

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
