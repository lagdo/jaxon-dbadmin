<?php

namespace Lagdo\DbAdmin\Db\Traits;

use Lagdo\DbAdmin\Driver\Entity\TableFieldEntity;

trait SelectQueryTrait
{
    /**
     * Filter length value including enums
     *
     * @param string $length
     *
     * @return string
     */
    public function processLength(string $length): string
    {
        if (!$length) {
            return '';
        }
        $enumLength = $this->driver->enumLength();
        return (\preg_match("~^\\s*\\(?\\s*$enumLength(?:\\s*,\\s*$enumLength)*+\\s*\\)?\\s*\$~", $length) &&
        \preg_match_all("~$enumLength~", $length, $matches) ? '(' . \implode(',', $matches[0]) . ')' :
            \preg_replace('~^[0-9].*~', '(\0)', \preg_replace('~[^-0-9,+()[\]]~', '', $length))
        );
    }

    /**
     * Create SQL string from field type
     *
     * @param TableFieldEntity $field
     * @param string $collate
     *
     * @return string
     */
    private function processType(TableFieldEntity $field, string $collate = 'COLLATE'): string
    {
        $values = [
            'unsigned' => $field->unsigned,
            'collation' => $field->collation,
        ];
        return ' ' . $field->type . $this->processLength($field->length) .
            (\preg_match($this->driver->numberRegex(), $field->type) &&
            \in_array($values['unsigned'], $this->driver->unsigned()) ?
                " {$values['unsigned']}" : '') . (\preg_match('~char|text|enum|set~', $field->type) &&
            $values['collation'] ? " $collate " . $this->driver->quote($values['collation']) : '');
    }

    /**
     * Create SQL string from field
     *
     * @param TableFieldEntity $field Basic field information
     * @param TableFieldEntity $typeField Information about field type
     *
     * @return array
     */
    public function processField(TableFieldEntity $field, TableFieldEntity $typeField): array
    {
        return [
            $this->driver->escapeId(trim($field->name)),
            $this->processType($typeField),
            ($field->null ? ' NULL' : ' NOT NULL'), // NULL for timestamp
            $this->driver->defaultValue($field),
            (\preg_match('~timestamp|datetime~', $field->type) && $field->onUpdate ?
                " ON UPDATE {$field->onUpdate}" : ''),
            ($this->driver->support('comment') && $field->comment != '' ?
                ' COMMENT ' . $this->driver->quote($field->comment) : ''),
            ($field->autoIncrement ? $this->driver->autoIncrement() : null),
        ];
    }

    /**
     * Apply SQL function
     *
     * @param string $function
     * @param string $column escaped column identifier
     *
     * @return string
     */
    public function applySqlFunction(string $function, string $column)
    {
        return ($function ? ($function == 'unixepoch' ?
            "DATETIME($column, '$function')" : ($function == 'count distinct' ?
                'COUNT(DISTINCT ' : strtoupper("$function(")) . "$column)") : $column);
    }

    /**
     * Process columns box in select
     *
     * @return array
     */
    public function processSelectColumns(): array
    {
        $select = []; // select expressions, empty for *
        $group = []; // expressions without aggregation - will be used for GROUP BY if an aggregation function is used
        foreach ((array) $this->input->values['columns'] as $key => $val) {
            if ($val['fun'] == 'count' ||
                ($val['col'] != '' && (!$val['fun'] ||
                        \in_array($val['fun'], $this->driver->functions()) ||
                        \in_array($val['fun'], $this->driver->grouping())))) {
                $select[$key] = $this->applySqlFunction(
                    $val['fun'],
                    ($val['col'] != '' ? $this->driver->escapeId($val['col']) : '*')
                );
                if (!in_array($val['fun'], $this->driver->grouping())) {
                    $group[] = $select[$key];
                }
            }
        }
        return [$select, $group];
    }

    /**
     * Process search box in select
     *
     * @param array $fields
     * @param array $indexes
     *
     * @return array
     */
    public function processSelectSearch(array $fields, array $indexes): array
    {
        $expressions = [];
        foreach ($indexes as $i => $index) {
            if ($index->type == 'FULLTEXT' && $this->input->values['fulltext'][$i] != '') {
                $columns = \array_map(function ($column) {
                    return $this->driver->escapeId($column);
                }, $index->columns);
                $expressions[] = 'MATCH (' . \implode(', ', $columns) . ') AGAINST (' .
                    $this->driver->quote($this->input->values['fulltext'][$i]) .
                    (isset($this->input->values['boolean'][$i]) ? ' IN BOOLEAN MODE' : '') . ')';
            }
        }
        foreach ((array) $this->input->values['where'] as $key => $val) {
            if ("$val[col]$val[val]" != '' && in_array($val['op'], $this->driver->operators())) {
                $prefix = '';
                $cond = " $val[op]";
                if (\preg_match('~IN$~', $val['op'])) {
                    $in = $this->processLength($val['val']);
                    $cond .= ' ' . ($in != '' ? $in : '(NULL)');
                } elseif ($val['op'] == 'SQL') {
                    $cond = " $val[val]"; // SQL injection
                } elseif ($val['op'] == 'LIKE %%') {
                    $cond = ' LIKE ' . $this->_processInput($fields[$val['col']], "%$val[val]%");
                } elseif ($val['op'] == 'ILIKE %%') {
                    $cond = ' ILIKE ' . $this->_processInput($fields[$val['col']], "%$val[val]%");
                } elseif ($val['op'] == 'FIND_IN_SET') {
                    $prefix = "$val[op](" . $this->driver->quote($val['val']) . ', ';
                    $cond = ')';
                } elseif (!\preg_match('~NULL$~', $val['op'])) {
                    $cond .= ' ' . $this->_processInput($fields[$val['col']], $val['val']);
                }
                if ($val['col'] != '') {
                    $expressions[] = $prefix . $this->driver->convertSearch(
                            $this->driver->escapeId($val['col']),
                            $val,
                            $fields[$val['col']]
                        ) . $cond;
                } else {
                    // find anywhere
                    $cols = [];
                    foreach ($fields as $name => $field) {
                        if ((\preg_match('~^[-\d.' . (\preg_match('~IN$~', $val['op']) ? ',' : '') . ']+$~', $val['val']) ||
                                !\preg_match('~' . $this->driver->numberRegex() . '|bit~', $field->type)) &&
                            (!\preg_match("~[\x80-\xFF]~", $val['val']) || \preg_match('~char|text|enum|set~', $field->type)) &&
                            (!\preg_match('~date|timestamp~', $field->type) || \preg_match('~^\d+-\d+-\d+~', $val['val']))
                        ) {
                            $cols[] = $prefix . $this->driver->convertSearch($this->driver->escapeId($name), $val, $field) . $cond;
                        }
                    }
                    $expressions[] = ($cols ? '(' . \implode(' OR ', $cols) . ')' : '1 = 0');
                }
            }
        }
        return $expressions;
    }

    /**
     * Process order box in select
     *
     * @return array
     */
    public function processSelectOrder(): array
    {
        $expressions = [];
        foreach ((array) $this->input->values['order'] as $key => $val) {
            if ($val != '') {
                $regexp = '~^((COUNT\(DISTINCT |[A-Z0-9_]+\()(`(?:[^`]|``)+`|"(?:[^"]|"")+")\)|COUNT\(\*\))$~';
                $expressions[] = (\preg_match($regexp, $val) ? $val : $this->driver->escapeId($val)) . //! MS SQL uses []
                    (isset($this->input->values['desc'][$key]) ? ' DESC' : '');
            }
        }
        return $expressions;
    }

    /**
     * Process limit box in select
     *
     * @return int
     */
    public function processSelectLimit(): int
    {
        return (isset($this->input->values['limit']) ? intval($this->input->values['limit']) : 50);
    }

    /**
     * Process length box in select
     *
     * @return int
     */
    public function processSelectLength(): int
    {
        return (isset($this->input->values['text_length']) ? intval($this->input->values['text_length']) : 100);
    }

    /**
     * Process extras in select form
     *
     * @param array $where AND conditions
     * @param array $foreignKeys
     *
     * @return bool
     */
    // public function processSelectEmail(array $where, array $foreignKeys)
    // {
    //     return false;
    // }
}
