<?php

namespace Lagdo\DbAdmin\Db\Driver\Facades;

use Lagdo\DbAdmin\Db\UiData\Ddl\ColumnInputDto;
use Lagdo\DbAdmin\Db\UiData\Ddl\ForeignKeyTrait;
use Lagdo\DbAdmin\Db\UiData\Ddl\TableAlter;
use Lagdo\DbAdmin\Db\UiData\Ddl\TableContent;
use Lagdo\DbAdmin\Db\UiData\Ddl\TableCreate;
use Lagdo\DbAdmin\Db\UiData\Ddl\TableHeader;
use Lagdo\DbAdmin\Driver\Dto\TableAlterDto;
use Lagdo\DbAdmin\Driver\Dto\TableCreateDto;
use Lagdo\DbAdmin\Driver\Dto\TableFieldDto;
use Exception;

use function array_map;
use function compact;
use function count;
use function in_array;

/**
 * Facade to table functions
 */
class TableFacade extends AbstractFacade
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
        return $this->tableHeader ??= new TableHeader($this->page, $this->driver, $this->utils);
    }

    /**
     * @return TableContent
     */
    private function content(): TableContent
    {
        return $this->tableContent ??= new TableContent($this->page, $this->driver, $this->utils);
    }

    /**
     * @return TableCreate
     */
    private function create(): TableCreate
    {
        return $this->tableCreate ??= new TableCreate($this->page, $this->driver, $this->utils);
    }

    /**
     * @return TableAlter
     */
    private function alter(): TableAlter
    {
        return $this->tableAlter ??= new TableAlter($this->page, $this->driver, $this->utils);
    }

    /**
     * Get field types
     *
     * @param string $type  The type name
     * @param array $extraTypes
     *
     * @return array
     */
    public function getFieldTypes(string $type = '', array $extraTypes = []): array
    {
        // From includes/editing.inc.php
        if ($type !== '' && !$this->driver->typeExists($type) &&
            !isset($this->foreignKeys[$type]) && !in_array($type, $extraTypes)) {
            $extraTypes[] = $type;
        }

        $structuredTypes = $this->driver->structuredTypes();
        if (!empty($this->foreignKeys)) {
            $structuredTypes[$this->utils->trans->lang('Foreign keys')] = $this->foreignKeys;
        }

        // Change from Adminer:
        // The $extraTypes are all kept in the first entry in the table.
        return count($extraTypes) > 0 ? [$extraTypes, ...$structuredTypes] : $structuredTypes;
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
        return $this->tableStatus ??= $this->driver->tableStatusOrName($table, true);
    }

    /**
     * Get details about a table
     *
     * @param string $table     The table name
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
     * Get the fields of a table
     *
     * @param string $table The table name
     *
     * @return array
     * @throws Exception
     */
    public function getTableFields(string $table): array
    {
        // From table.inc.php
        $fields = $this->driver->fields($table);
        if (empty($fields)) {
            throw new Exception($this->driver->error());
        }

        $status = $this->status($table);

        return [
            'headers' => $this->header()->fields(),
            'details' => $this->content()->fields($fields, $status?->collation ?? ''),
        ];
    }

    /**
     * Get the indexes of a table
     *
     * @param string $table     The table name
     *
     * @return array|null
     */
    public function getTableIndexes(string $table): ?array
    {
        if (!$this->driver->support('indexes')) {
            return null;
        }

        // From table.inc.php
        $indexes = $this->driver->indexes($table);

        return [
            'headers' => $this->header()->indexes(),
            'details' => $this->content()->indexes($indexes),
        ];
    }

    /**
     * Get the foreign keys of a table
     *
     * @param string $table     The table name
     *
     * @return array|null
     */
    public function getTableForeignKeys(string $table): ?array
    {
        $status = $this->status($table);
        if (!$this->driver->supportForeignKeys($status)) {
            return null;
        }

        $foreignKeys = $this->driver->foreignKeys($table);

        return [
            'headers' => $this->header()->foreignKeys(),
            'details' => $this->content()->foreignKeys($foreignKeys),
        ];
    }

    /**
     * Get the triggers of a table
     *
     * @param string $table     The table name
     *
     * @return array|null
     */
    public function getTableTriggers(string $table): ?array
    {
        if (!$this->driver->support('trigger')) {
            return null;
        }

        // From table.inc.php
        $triggers = $this->driver->triggers($table);

        return [
            'headers' => $this->header()->triggers(),
            'details' => $this->content()->triggers($triggers),
        ];
    }

    /**
     * Get required data for create/update on tables
     *
     * @param string $table The table name
     *
     * @return array
     * @throws Exception
     */
    public function getTableMetadata(string $table = ''): array
    {
        // From create.inc.php
        $status = null;
        $fields = [];
        if ($table !== '') {
            $status = $this->driver->tableStatus($table);
            if (!$status) {
                throw new Exception($this->utils->trans->lang('No tables.'));
            }
            $fields = $this->driver->fields($table);
        }

        $this->getForeignKeys($table);

        $fields = array_map(function($field) {
            $field->types = $this->getFieldTypes($field->type);
            return $field;
        }, $fields);

        return $this->content()->metadata($status, $fields, $this->foreignKeys);
    }

    /**
     * Get fields for a new column
     *
     * @return TableFieldDto
     */
    public function getTableField(): TableFieldDto
    {
        $this->getForeignKeys();
        $field = new TableFieldDto();
        $field->types = $this->getFieldTypes();
        return $field;
    }

    /**
     * Get SQL command to create a table
     *
     * @param array $options     The table options
     * @param array<ColumnInputDto> $columns
     *
     * @return array
     */
    public function getCreateTableQueries(array $options, array $columns): array
    {
        $table = new TableCreateDto($options);
        $table = $this->create()->makeDto($table, $columns);
        if ($table->error !== null) {
            return[
                'error' => $table->error,
            ];
        }

        return [
            'queries' => $this->driver->getTableCreationQueries($table),
        ];
    }

    /**
     * Create a table
     *
     * @param array $options     The table options
     * @param array<ColumnInputDto> $columns
     *
     * @return array
     */
    public function createTable(array $options, array $columns): array
    {
        $queries = $this->getCreateTableQueries($options, $columns);

        return compact('success', 'error', 'message');
    }

    /**
     * Get SQL command to alter a table
     *
     * @param string $name       The table name
     * @param array $options     The table options
     * @param array<ColumnInputDto> $columns
     *
     * @return array
     */
    public function getAlterTableQueries(string $name, array $options, array $columns): array
    {
        $table = new TableAlterDto($options);
        if (($table->current = $this->driver->tableStatus($name, true)) === null) {
            return[
                'error' => $this->utils->trans->lang('Unable to find the table.'),
            ];
        }

        $table = $this->alter()->makeDto($table, $columns);
        if ($table->error !== null) {
            return[
                'error' => $table->error,
            ];
        }

        return [
            'queries' => $this->driver->getTableAlterationQueries($table),
        ];
    }

    /**
     * Alter a table
     *
     * @param string $name       The table name
     * @param array $options     The table options
     * @param array<ColumnInputDto> $columns
     *
     * @return array
     * @throws Exception
     */
    public function alterTable(string $name, array $options, array $columns): array
    {
        $queries = $this->getAlterTableQueries($name, $options, $columns);

        return compact('success', 'error', 'message');
    }

    /**
     * Drop a table
     *
     * @param string $table     The table name
     *
     * @return array
     */
    public function dropTable(string $table): array
    {
        if ($this->driver->tableStatus($table) === null) {
            return [
                'error' => $this->utils->trans->lang('Invalid table %s.', $table),
            ];
        }

        if (!$this->driver->dropTables([$table])) {
            return [
                'error' => $this->driver->error(),
            ];
        }

        return [
            'message' => $this->utils->trans->lang('Table has been dropped.'),
        ];
    }
}
