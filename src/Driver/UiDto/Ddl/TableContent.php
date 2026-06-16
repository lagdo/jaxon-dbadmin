<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto\Ddl;

use Lagdo\DbAdmin\Support\Driver\AbstractDriverProxy;
use Lagdo\DbAdmin\Driver\Sql\Dto\ColumnAction;
use Lagdo\DbAdmin\Driver\Sql\Dto\ColumnDdDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\ColumnDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\ForeignKeyDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\IndexDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\TableAlterDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\TableCreateDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\TableDdDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\TableDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\TriggerDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\DetailDto;

use function array_filter;
use function array_flip;
use function array_keys;
use function array_key_exists;
use function array_map;
use function array_values;
use function count;
use function implode;
use function in_array;
use function is_string;
use function ksort;
use function str_replace;

class TableContent extends AbstractDriverProxy
{
    /**
     * @param array<ColumnDto> $columns
     * @param string $tableCollation
     *
     * @return array<DetailDto>
     */
    public function columns(array $columns, string $tableCollation): array
    {
        $userTypes = $this->engine()->structuredTypes()[$this->utils()->lang('User types')] ?? [];

        return array_map(function(ColumnDto $column) use($tableCollation, $userTypes) {
            $commentSupported = $this->engine()->support('comment');
            $content = [
                'name' => $this->utils()->html($column->name),
                'type' => $this->pageUi()->getColumnType($column, $tableCollation),
                'collation' => $this->utils()->html($column->collation),
            ];

            $fullType = $this->utils()->html($column->fullType);
            if (in_array($fullType, $userTypes)) {
                $content['references'] = $fullType;
            }

            if ($commentSupported) {
                $content['comment'] = $column->comment === null ? null :
                    $this->utils()->html($column->comment);
            }

            return new DetailDto($content);
        }, $columns);
    }

    /**
     * @param array<IndexDto> $indexes
     *
     * @return array<DetailDto>
     */
    public function indexes(array $indexes): array
    {
        // From adminer.inc.php
        return array_map(function(IndexDto $index, string $name) {
            ksort($index->columns); // enforce correct columns order
            $print = [];
            foreach ($index->columns as $key => $val) {
                $value = '<i>' . $this->utils()->html($val) . '</i>';
                if (array_key_exists($key, $index->lengths)) {
                    $value .= '(' . $index->lengths[$key] . ')';
                }
                $print[] = $value;
            }

            return new DetailDto([
                'name' => $this->utils()->html($name),
                'type' => $index->type,
                'desc' => implode(', ', $print),
            ]);
        }, $indexes, array_keys($indexes));
    }

    /**
     * @param array<ForeignKeyDto> $foreignKeys
     *
     * @return array<DetailDto>
     */
    public function foreignKeys(array $foreignKeys): array
    {
        // From table.inc.php
        $keyCallback = $this->utils()->html(...);
        return array_map(function(ForeignKeyDto $foreignKey, string $name) use($keyCallback) {
            $target = '';
            if ($foreignKey->database != '') {
                $target .= '<b>' . $this->utils()->html($foreignKey->database) . '</b>.';
            }
            if ($foreignKey->schema != '') {
                $target .= '<b>' . $this->utils()->html($foreignKey->schema) . '</b>.';
            }
            $targets = array_map($keyCallback, $foreignKey->target);
            $target = $this->utils()->html($foreignKey->table) .
                '(' . implode(', ', $targets) . ')';
            $sources = array_map($keyCallback, $foreignKey->source);

            return new DetailDto([
                'name' => $this->utils()->html($name),
                'source' => '<i>' . implode('</i>, <i>', $sources) . '</i>',
                'target' => $target,
                'onDelete' => $this->utils()->html($foreignKey->onDelete),
                'onUpdate' => $this->utils()->html($foreignKey->onUpdate),
            ]);
        }, $foreignKeys, array_keys($foreignKeys));
    }

    /**
     * @param array<TriggerDto> $triggers
     *
     * @return array<DetailDto>
     */
    public function triggers(array $triggers): array
    {
        return array_map(fn(TriggerDto $trigger, string $name) => new DetailDto([
            $this->utils()->html($trigger->timing),
            $this->utils()->html($trigger->event),
            $this->utils()->html($name),
            $this->utils()->lang('Alter'),
        ]), $triggers, array_keys($triggers));
    }

    /**
     * Get column types
     *
     * @param string $type  The type name
     * @param array $extraTypes
     * @param array<string,string> $foreignKeys
     *
     * @return array
     */
    private function getColumnTypes(string $type = '',
        array $extraTypes = [], array $foreignKeys = []): array
    {
        // From includes/editing.inc.php
        if ($type !== '' && !$this->engine()->typeExists($type) &&
            !isset($foreignKeys[$type]) && !in_array($type, $extraTypes)) {
            $extraTypes[] = $type;
        }

        $structuredTypes = $this->engine()->structuredTypes();
        if (!empty($foreignKeys)) {
            $structuredTypes[$this->utils()->lang('Foreign keys')] = $foreignKeys;
        }

        // Change from Adminer:
        // The $extraTypes are all kept in the first entry in the table.
        return count($extraTypes) > 0 ? [$extraTypes, ...$structuredTypes] : $structuredTypes;
    }

    /**
     * @param ColumnDto $column
     * @param array<string,string> $foreignKeys
     *
     * @return ColumnFormDto
     */
    private function getColumnInput(ColumnDto $column, array $foreignKeys): ColumnFormDto
    {
        $types = $this->getColumnTypes($column->type, foreignKeys: $foreignKeys);
        return new ColumnFormDto($column, types: $types);
    }

    /**
     * @param TableDto $status
     * @param array<string,string> $foreignKeys
     * 
     * @return array
     */
    public function metadata(TableDto $status, array $foreignKeys): array
    {
        $collations = $this->engine()->collations();
        $unsigned = $this->engine()->unsigned();
        $columns = array_map(fn(ColumnDto $column) =>
            $this->getColumnInput($column, $foreignKeys), $status->columns());
        $table = new TableFormDto($status, $columns);

        return [
            'table' => $table,
            'foreignKeys' => $foreignKeys,
            'options' => [
                'onUpdate' => ['CURRENT_TIMESTAMP' => 'CURRENT_TIMESTAMP'],
                'onDelete' => $this->engine()->onActions(),
            ],
            'collations' => $collations,
            'unsigned' => $unsigned,
            'engines' => $this->engine()->engines(),
            'defaults' => $this->engine()->columnDefaults(),
            // Callback to call the support method.
            'support' => $this->engine()->support(...),
        ];
    }

    /**
     * @param ColumnFormDto $input
     * @param array<string,string> $foreignKeys
     *
     * @return ColumnFormDto
     */
    public function setInputFieldProperties(ColumnFormDto $input, array $foreignKeys): ColumnFormDto
    {
        // Todo: enable this.
        // See the edit_type() function in the editing.inc.php file.

        // $collations = $this->engine()->collations();
        // $unsigned = $this->engine()->unsigned();

        // $input->lengthRequired = $input->column->length === '' &&
        //     preg_match('~var(char|binary)$~', $input->column->type) > 0;
        // $input->collationEditable = count($collations) > 0 &&
        //     preg_match('~(char|text|enum|set)$~', $input->column->type) > 0;
        // $input->unsignedEditable = count($unsigned) > 0 && ($input->column->type &&
        //     preg_match($this->engine()->numberRegex(), $input->column->type) > 0);
        // $input->onUpdateEditable = $input->column->onUpdate !== '' &&
        //     preg_match('~timestamp|datetime~', $input->column->type) > 0;
        // $input->onDeleteEditable = count($foreignKeys) > 0 &&
        //     preg_match('~`~', $input->column->type) > 0;

        return $input;
    }

    /**
     * Get a new table column
     *
     * @param array|null $values
     * @param array<string,string> $foreignKeys
     * 
     * @return ColumnFormDto
     */
    public function newColumnInput(array|null $values, array $foreignKeys): ColumnFormDto
    {
        $input = $this->getColumnInput(new ColumnDto(), $foreignKeys);
        if ($values !== null) {
            $input->setValues($values);
        }

        return $this->setInputFieldProperties($input, $foreignKeys);
    }

    /**
     * @param string $table
     *
     * @return array<ColumnDto>
     */
    private function getReferencableColumns(string $table): array
    {
        // From editing.inc.php, function referencable_primary()

        $filter = fn(TableDto $tableStatus, string $tableName) =>
            $tableName != $table && $this->engine()->supportForeignKeys($tableStatus);
        $tables = $this->engine()->tableStatuses(true);
        $tables = array_filter($tables, $filter, ARRAY_FILTER_USE_BOTH);

        $filter = fn(ColumnDto $column) => $column->primary;
        $primaryColumns = array_map(fn(TableDto $tableStatus) =>
            array_values(array_filter($tableStatus->columns(), $filter)), $tables);

        // Remove multi column primary keys
        $filter = fn(array $columns) => count($columns) === 1;
        $primaryColumns = array_filter($primaryColumns, $filter);

        return array_map(fn(array $columns) => $columns[0], $primaryColumns);
    }

    /**
     * Get foreign keys
     *
     * @param TableDdDto|string $table
     *
     * @return array
     */
    private function getForeignKeys(TableDdDto|string $table = ''): array
    {
        $columns = is_string($table) ?
            $this->getReferencableColumns($table) :
            $table->getReferencableColumns();

        $replace = fn(string $name) => str_replace("`", "``", $name);
        $convertName = fn(ColumnDto $column, string $tableName) =>
            $replace($tableName) . "`" . $replace($column->name); // not escapeId() - used in JS
        $foreignKeys = array_map($convertName, $columns, array_keys($columns));

        return array_flip($foreignKeys);
    }

    /**
     * @param TableDdDto $table
     * @param ColumnAction $action
     * @param ColumnFormDto $input
     * @param array $foreignKeys
     *
     * @return ColumnDdDto
     */
    private function makeColumnInput(TableDdDto $table, ColumnAction $action,
        ColumnFormDto $input, array $foreignKeys): ColumnDdDto
    {
        $values = $input->values();

        //! can collide with user defined type
        $foreignKey = $foreignKeys[$values->type] ?? '';
        $typeColumn = $table->getReferencableColumns()[$foreignKey] ?? null;
        if ($typeColumn !== null) {
            $fkColumn = new ForeignKeyDto();
            $fkColumn->table = $foreignKey;
            $fkColumn->source = [$values->name];
            $fkColumn->target = [$typeColumn->name];
            $fkColumn->onDelete = $values->onDelete;

            $table->foreignKeys[$values->name] = $fkColumn;
        }

        $column = new ColumnDdDto($input->column, $typeColumn);
        foreach ($input->attributes() as $attr) {
            $column->$attr = $values->$attr;
        }
        if ($values->generated === '') {
            $column->default = null;
        }
        if (!$values->setComment) {
            $column->comment = null;
        }
        if ($action === ColumnAction::ADD && $column->autoIncrement) {
            $column->type = $this->statement()->getAutoIncrementType($column->type);
        }

        return $column;
    }

    /**
     * @param TableFormDto $table
     * 
     * @return TableCreateDto
     */
    public function makeCreateDto(TableFormDto $table): TableCreateDto
    {
        $values = (array)$table->values();
        $createDto = new TableCreateDto($values, $this->getReferencableColumns(...));
        // From create.inc.php
        $foreignKeys = $this->getForeignKeys();

        // $after = " FIRST";

        $createDto->clearColumns();
        $createDto->columns[ColumnAction::ADD->value] = array_map(fn(ColumnFormDto $input) =>
            $this->makeColumnInput($createDto, ColumnAction::ADD, $input, $foreignKeys),
            array_filter($table->columns, fn(ColumnFormDto $input) => $input->added()));

        // Auto increment
        if ($createDto->autoIncrementColumnCount() > 1) {
            $createDto->error = $this->utils()->lang('Only one auto-increment column is allowed.');
        }

        return $createDto;
    }

    /**
     * @param TableFormDto $table
     * 
     * @return TableAlterDto
     */
    public function makeAlterDto(TableFormDto $table): TableAlterDto
    {
        $values = (array)$table->values();
        $alterDto = new TableAlterDto($values, $this->getReferencableColumns(...));
        $alterDto->status = $table->status;

        // From create.inc.php
        $foreignKeys = $this->getForeignKeys($table->status->name);

        // Todo: move fields up and down
        // $after = " FIRST";

        $alterDto->clearColumns();
        $alterDto->columns[ColumnAction::ADD->value] = array_map(fn(ColumnFormDto $input) =>
            $this->makeColumnInput($alterDto, ColumnAction::ADD, $input, $foreignKeys),
            array_filter($table->columns, fn(ColumnFormDto $input) => $input->added()));
        $alterDto->columns[ColumnAction::EDIT->value] = array_map(fn(ColumnFormDto $input) =>
            $this->makeColumnInput($alterDto, ColumnAction::EDIT, $input, $foreignKeys),
            array_filter($table->columns, fn(ColumnFormDto $input) => $input->changed()));
        $alterDto->columns[ColumnAction::DROP->value] = array_map(
            fn(ColumnFormDto $input) => $input->column->name,
            array_filter($table->columns, fn(ColumnFormDto $input) => $input->dropped()));

        // Auto increment
        if ($alterDto->autoIncrementColumnCount() > 1) {
            $alterDto->error = $this->utils()->lang('Only one auto-increment column is allowed.');
        }

        return $alterDto;
    }
}
