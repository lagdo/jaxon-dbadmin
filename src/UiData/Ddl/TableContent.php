<?php

namespace Lagdo\DbAdmin\Db\UiData\Ddl;

use Lagdo\DbAdmin\Db\UiData\AppPage;
use Lagdo\DbAdmin\Driver\DriverInterface;
use Lagdo\DbAdmin\Driver\Dto\ForeignKeyDto;
use Lagdo\DbAdmin\Driver\Dto\IndexDto;
use Lagdo\DbAdmin\Driver\Dto\TableDto;
use Lagdo\DbAdmin\Driver\Dto\TableFieldDto;
use Lagdo\DbAdmin\Driver\Dto\TriggerDto;
use Lagdo\DbAdmin\Driver\Utils\Utils;

use function array_key_exists;
use function array_map;
use function implode;
use function in_array;
use function ksort;
use function preg_match;

class TableContent
{
    /**
     * The constructor
     *
     * @param AppPage $page
     * @param DriverInterface $driver
     * @param Utils $utils
     */
    public function __construct(private AppPage $page,
        private DriverInterface $driver, private Utils $utils)
    {}

    /**
     * @param array<TableFieldDto> $fields
     * @param string $tableCollation
     *
     * @return array
     */
    public function fields(array $fields, string $tableCollation): array
    {
        $commentSupported = $this->driver->support('comment');
        $userTypes = $this->driver->structuredTypes()[$this->utils->trans->lang('User types')] ?? [];

        $contents = [];
        foreach ($fields as $field) {
            $content = [
                'name' => $this->utils->str->html($field->name),
                'type' => $this->page->getTableFieldType($field, $tableCollation),
                'collation' => $this->utils->str->html($field->collation),
            ];

            $fullType = $this->utils->str->html($field->fullType);
            if (in_array($fullType, $userTypes)) {
                $content['references'] = $fullType;
            }

            if ($commentSupported) {
                $content['comment'] = $this->utils->str->html($field->comment);
            }

            $contents[] = $content;
        }

        return $contents;
    }

    /**
     * @param array<IndexDto> $indexes
     *
     * @return array
     */
    public function indexes(array $indexes): array
    {
        $contents = [];
        // From adminer.inc.php
        foreach ($indexes as $name => $index) {
            ksort($index->columns); // enforce correct columns order
            $print = [];
            foreach ($index->columns as $key => $val) {
                $value = '<i>' . $this->utils->str->html($val) . '</i>';
                if (array_key_exists($key, $index->lengths)) {
                    $value .= '(' . $index->lengths[$key] . ')';
                }
                if (array_key_exists($key, $index->descs)) {
                    $value .= ' DESC';
                }
                $print[] = $value;
            }
            $contents[] = [
                'name' => $this->utils->str->html($name),
                'type' => $index->type,
                'desc' => implode(', ', $print),
            ];
        }

        return $contents;
    }

    /**
     * @param array<ForeignKeyDto> $foreignKeys
     *
     * @return array
     */
    public function foreignKeys(array $foreignKeys): array
    {
        $contents = [];
        // From table.inc.php
        $keyCallback = fn ($key) => $this->utils->html($key);
        foreach ($foreignKeys as $name => $foreignKey) {
            $target = '';
            if ($foreignKey->database != '') {
                $target .= '<b>' . $this->utils->str->html($foreignKey->database) . '</b>.';
            }
            if ($foreignKey->schema != '') {
                $target .= '<b>' . $this->utils->str->html($foreignKey->schema) . '</b>.';
            }
            $targets = array_map($keyCallback, $foreignKey->target);
            $target = $this->utils->str->html($foreignKey->table) .
                '(' . implode(', ', $targets) . ')';
            $sources = array_map($keyCallback, $foreignKey->source);
            $contents[] = [
                'name' => $this->utils->str->html($name),
                'source' => '<i>' . implode('</i>, <i>', $sources) . '</i>',
                'target' => $target,
                'onDelete' => $this->utils->str->html($foreignKey->onDelete),
                'onUpdate' => $this->utils->str->html($foreignKey->onUpdate),
            ];
        }

        return $contents;
    }

    /**
     * @param array<TriggerDto> $triggers
     *
     * @return array
     */
    public function triggers(array $triggers): array
    {
        $contents = [];
        foreach ($triggers as $name => $trigger) {
            $contents[] = [
                $this->utils->str->html($trigger->timing),
                $this->utils->str->html($trigger->event),
                $this->utils->str->html($name),
                $this->utils->trans->lang('Alter'),
            ];
        }

        return $contents;
    }

    /**
     * @param TableDto|null $status
     * @param array<TableFieldDto> $fields
     * @param array<string,string> $foreignKeys
     * 
     * @return array
     */
    public function metadata(TableDto|null $status, array $fields, array $foreignKeys): array
    {
        $hasAutoIncrement = false;
        $fields = array_map(function($field) use(&$hasAutoIncrement) {
            $hasAutoIncrement = $hasAutoIncrement || $field->autoIncrement;
            if (preg_match('~^CURRENT_TIMESTAMP~i', $field->onUpdate)) {
                $field->onUpdate = 'CURRENT_TIMESTAMP';
            }

            // Todo: check that these flags are set properly.
            // $type = $field->type;
            $field->lengthRequired = true; // !$field->length && preg_match('~var(char|binary)$~', $type);
            $field->collationHidden = false; // !preg_match('~(char|text|enum|set)$~', $type);
            $field->unsignedHidden = false; // $type && !preg_match($this->driver->numberRegex(), $type);
            $field->onUpdateHidden = false; // !preg_match('~timestamp|datetime~', $type);
            $field->onDeleteHidden = false; // !preg_match('~`~', $type);

            return $field;
        }, $fields);

        return [
            'table' => $status,
            'foreignKeys' => $foreignKeys,
            'fields' => $fields,
            'options' => [
                'hasAutoIncrement' => $hasAutoIncrement,
                'onUpdate' => ['CURRENT_TIMESTAMP' => 'CURRENT_TIMESTAMP'],
                'onDelete' => $this->driver->onActions(),
            ],
            'collations' => $this->driver->collations(),
            'engines' => $this->driver->engines(),
            'defaults' => $this->driver->fieldDefaults(),
            'support' => [
                'columns' => $this->driver->support('columns'),
                'comment' => $this->driver->support('comment'),
                'partitioning' => $this->driver->support('partitioning'),
                'move_col' => $this->driver->support('move_col'),
                'drop_col' => $this->driver->support('drop_col'),
            ],
            'unsigned' => $this->driver->unsigned(),
        ];
    }
}
