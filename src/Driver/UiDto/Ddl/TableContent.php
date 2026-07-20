<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto\Ddl;

use Lagdo\DbAdmin\Driver\Sql\Dto\ColumnAction;
use Lagdo\DbAdmin\Driver\Sql\Dto\ColumnDdDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\ColumnDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\ForeignKeyDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\IndexDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\TableAlterDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\TableCreateDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\TableDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\TriggerDto;
use Lagdo\DbAdmin\Support\Driver\AbstractDriverProxy;
use Lagdo\DbAdmin\Support\Driver\UiDto\DetailDto;

use function array_combine;
use function array_filter;
use function array_keys;
use function array_key_exists;
use function array_map;
use function array_values;
use function count;
use function implode;
use function in_array;
use function is_numeric;
use function ksort;

class TableContent extends AbstractDriverProxy
{
    use ForeignKeyTrait;

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
     * @param string $table
     *
     * @return array<DetailDto>
     */
    public function foreignKeys(string $table): array
    {
        $foreignKeys = $this->engine()->foreignKeys($table);
        $keyCallback = $this->utils()->html(...);
        return array_map(function(ForeignKeyDto $foreignKey, string $name) use($keyCallback) {
            $target = '';
            if ($foreignKey->database != '') {
                $target .= '<b>' . $this->utils()->html($foreignKey->database) . '</b>.';
            }
            if ($foreignKey->schema != '') {
                $target .= '<b>' . $this->utils()->html($foreignKey->schema) . '</b>.';
            }
            $targets = implode(', ', array_map($keyCallback, $foreignKey->target));
            $target = $this->utils()->html($foreignKey->table) . "($targets)";
            $sources = array_map($keyCallback, $foreignKey->source);

            return new DetailDto([
                'name' => is_numeric($name) ? '' : $this->utils()->html($name),
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
     *
     * @return array
     */
    private function getColumnTypes(string $type): array
    {
        $extraTypes = [];
        if ($type !== '' && !$this->engine()->typeExists($type) &&
            !isset($referencableColumns[$type]) && !in_array($type, $extraTypes)) {
            $extraTypes[] = $type;
        }
        $structuredTypes = $this->engine()->structuredTypes();
        // Change from Adminer:
        // The $extraTypes are all kept in the first entry in the table.
        return count($extraTypes) > 0 ? [$extraTypes, ...$structuredTypes] : $structuredTypes;
    }

    /**
     * @param ColumnDto $column
     * @param ForeignKeyDdDto|null $foreignKey
     *
     * @return ColumnFormDto
     */
    private function getColumnInput(ColumnDto $column, ForeignKeyDdDto|null $foreignKey): ColumnFormDto
    {
        $types = $this->getColumnTypes($column->type);
        return new ColumnFormDto($column, $foreignKey, $types);
    }

    /**
     * @param string $table
     *
     * @return array<ForeignKeyDdDto>
     */
    private function getForeignKeysForDropDown(string $table): array
    {
        if ($table === '') {
            return [];
        }

        $foreignKeys = array_filter($this->engine()->foreignKeys($table),
            fn(ForeignKeyDto $foreignKey) => count($foreignKey->source) === 1);
        $dtoBuilder = function(ForeignKeyDto $foreignKey, string $name) {
            $dto = new ForeignKeyDdDto();
            $dto->name = is_numeric($name) ? '' : $name;;
            $dto->table = $foreignKey->table;
            $dto->column = $foreignKey->target[0];
            $dto->onUpdate = $foreignKey->onUpdate;
            $dto->onDelete = $foreignKey->onDelete;
            $dto->deferrable = $foreignKey->deferrable;
            return $dto;
        };
        $foreignKeyDtos = array_map($dtoBuilder, $foreignKeys, array_keys($foreignKeys));
        // Key by source column name.
        $sourceGetter = fn(ForeignKeyDto $foreignKey) => $foreignKey->source[0];
        $foreignKeySources = array_map($sourceGetter, $foreignKeys);

        return array_combine($foreignKeySources, $foreignKeyDtos);
    }

    /**
     * @param TableDto $status
     *
     * @return array
     */
    public function metadata(TableDto $status): array
    {
        $referencableColumns = $this->getReferencableColumns();
        $referencableColumns = $this->formatReferencableColumns($referencableColumns);
        $collations = $this->engine()->collations();
        $unsigned = $this->engine()->unsigned();
        $foreignKeys = $this->getForeignKeysForDropDown($status->name);

        $inputGetter = fn(ColumnDto $column) =>
            $this->getColumnInput($column, $foreignKeys[$column->name] ?? null);
        $columns = array_map($inputGetter, $status->columns());
        $table = new TableFormDto($status, $columns, $referencableColumns);
        $foreignKeyActions = $this->engine()->onActions();

        return [
            'table' => $table,
            'options' => [
                'column' => [
                    'onUpdate' => ['CURRENT_TIMESTAMP' => 'CURRENT_TIMESTAMP'],
                ],
                'foreignKey' => [
                    'onUpdate' => $foreignKeyActions,
                    'onDelete' => $foreignKeyActions,
                ],
            ],
            'collations' => $collations,
            'unsigned' => $unsigned,
            'engine' => $this->engine(),
        ];
    }

    /**
     * Get a new table column
     *
     * @param array|null $values
     *
     * @return ColumnFormDto
     */
    public function newColumnInput(array|null $values): ColumnFormDto
    {
        $foreignKey = null;
        if ($values !== null && ($fkId = $values['foreignKey']) !== '') {
            $foreignKey = new ForeignKeyDdDto();
            [$foreignKey->table, $foreignKey->column] = explode('::', $fkId);
            $foreignKey->onUpdate = $values['fkOnUpdate'];
            $foreignKey->onDelete = $values['fkOnDelete'];
            $foreignKey->deferrable = $values['fkDeferrable'];
        }
        $input = $this->getColumnInput(new ColumnDto(), $foreignKey);
        if ($values !== null) {
            $input->setValues($values);
        }
        return $input;
    }

    /**
     * @param ColumnFormDto $input
     *
     * @return ColumnDdDto
     */
    private function makeColumnInput(ColumnFormDto $input): ColumnDdDto
    {
        $values = $input->values();

        $column = new ColumnDdDto($input->column, $input->action());
        switch ($input->action()) {
        case ColumnAction::DROP:
            $column->name = $values->name;
            $column->type = $values->type;
            break;
        case ColumnAction::NONE:
            $column->name = $values->name;
            $column->type = $values->type;
            $column->primary = $values->primary;
            $column->autoIncrement = $values->autoIncrement;
            break;
        default:
            foreach ($input->attributes() as $attr) {
                $column->$attr = $values->$attr;
            }
            if ($values->generated === '') {
                $column->default = null;
            }
            if (!$values->setComment) {
                $column->comment = null;
            }
            if ($input->added() && $column->autoIncrement) {
                $column->type = $this->statement()->getAutoIncrementType($column->type);
            }
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
        $autoIncrementCount = count(array_filter($table->columns,
            fn(ColumnFormDto $input) => $input->values()->autoIncrement));
        if ($autoIncrementCount > 1) {
            $errorDto = new TableCreateDto([], []);
            $errorDto->error = $this->utils()
                ->lang('Only one auto-increment column is allowed.');
            return $errorDto;
        }

        $columns = array_values(array_map($this->makeColumnInput(...), $table->columns));
        $createDto = new TableCreateDto((array)$table->values(), $columns);

        $foreignKeys = $this->getForeignKeys();
        $createDto->setForeignKeys($foreignKeys, $this->getReferencableColumns());

        return $createDto;
    }

    /**
     * @param TableFormDto $table
     *
     * @return TableAlterDto
     */
    public function makeAlterDto(TableFormDto $table): TableAlterDto
    {
        $autoIncrementCount = count(array_filter($table->columns,
            fn(ColumnFormDto $input) => $input->values()->autoIncrement));
        if ($autoIncrementCount > 1) {
            $errorDto = new TableAlterDto([], []);
            $errorDto->error = $this->utils()
                ->lang('Only one auto-increment column is allowed.');
            return $errorDto;
        }

        $columns = array_values(array_map($this->makeColumnInput(...), $table->columns));
        $alterDto = new TableAlterDto((array)$table->values(), $columns);
        $alterDto->status = $table->status;

        $foreignKeys = $this->getForeignKeys($table->status->name);
        $alterDto->setForeignKeys($foreignKeys, $this->getReferencableColumns());

        return $alterDto;
    }
}
