<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto\Ddl;

use Lagdo\DbAdmin\Support\Driver\AbstractDriverProxy;
use Lagdo\DbAdmin\Driver\Sql\Dto\ColumnDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\ForeignKeyDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\IndexDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\TableDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\TriggerDto;
use Lagdo\DbAdmin\Support\Driver\UiDto\DetailDto;

use function array_keys;
use function array_key_exists;
use function array_map;
use function count;
use function implode;
use function in_array;
use function ksort;
use function preg_match;

class TableContent extends AbstractDriverProxy
{
    /**
     * @param array<ColumnDto> $columns
     * @param string $tableCollation
     *
     * @return array
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
     * @return array
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
     * @return array
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
     * @return array
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
     * @return ColumnDdDto
     */
    private function getColumnInput(ColumnDto $column, array $foreignKeys): ColumnDdDto
    {
        $types = $this->getColumnTypes($column->type, foreignKeys: $foreignKeys);
        return new ColumnDdDto($column, types: $types);
    }

    /**
     * @param TableDto|null $status
     * @param array<ColumnDto> $columns
     * @param array<string,string> $foreignKeys
     * 
     * @return array
     */
    public function metadata(TableDto|null $status, array $columns, array $foreignKeys): array
    {
        $collations = $this->engine()->collations();
        $unsigned = $this->engine()->unsigned();

        $hasAutoIncrement = false;
        foreach ($columns as $column) {
            $hasAutoIncrement = $hasAutoIncrement || $column->autoIncrement;
            if (preg_match('~^CURRENT_TIMESTAMP~i', $column->onUpdate)) {
                $column->onUpdate = 'CURRENT_TIMESTAMP';
            }
        }

        return [
            'table' => $status,
            'foreignKeys' => $foreignKeys,
            'columns' => array_map(fn(ColumnDto $column) =>
                $this->getColumnInput($column, $foreignKeys), $columns),
            'options' => [
                'hasAutoIncrement' => $hasAutoIncrement,
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
     * @param ColumnDdDto $input
     * @param array<string,string> $foreignKeys
     *
     * @return ColumnDdDto
     */
    public function setInputFieldProperties(ColumnDdDto $input, array $foreignKeys): ColumnDdDto
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
     * @param array<string,string> $foreignKeys
     * 
     * @return ColumnDdDto
     */
    public function newColumnInput(array $foreignKeys): ColumnDdDto
    {
        $input = $this->getColumnInput(new ColumnDto(), $foreignKeys);
        return $this->setInputFieldProperties($input, $foreignKeys);
    }
}
