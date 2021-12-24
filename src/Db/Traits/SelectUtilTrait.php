<?php

namespace Lagdo\DbAdmin\Db\Traits;

use Lagdo\DbAdmin\Driver\Entity\IndexEntity;
use Lagdo\DbAdmin\Driver\Entity\TableFieldEntity;

use function intval;
use function implode;
use function strtoupper;
use function in_array;
use function array_map;
use function preg_match;
use function preg_replace;
use function preg_match_all;

trait SelectUtilTrait
{
    /**
     * @param TableFieldEntity $field Single field from fields()
     * @param string $value
     * @param string $function
     *
     * @return string
     */
    abstract protected function getUnconvertedFieldValue(TableFieldEntity $field, string $value, string $function = ''): string;

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
        if (preg_match("~^\\s*\\(?\\s*$enumLength(?:\\s*,\\s*$enumLength)*+\\s*\\)?\\s*\$~", $length) &&
            preg_match_all("~$enumLength~", $length, $matches)) {
            return '(' . implode(',', $matches[0]) . ')';
        }
        return preg_replace('~^[0-9].*~', '(\0)', preg_replace('~[^-0-9,+()[\]]~', '', $length));
    }

    /**
     * Process limit box in select
     *
     * @return int
     */
    public function processSelectLimit(): int
    {
        return isset($this->input->values['limit']) ? intval($this->input->values['limit']) : 50;
    }

    /**
     * Process length box in select
     *
     * @return int
     */
    public function processSelectLength(): int
    {
        return isset($this->input->values['text_length']) ? intval($this->input->values['text_length']) : 100;
    }

    /**
     * Process order box in select
     *
     * @return array
     */
    public function processSelectOrder(): array
    {
        $expressions = [];
        foreach ((array) $this->input->values['order'] as $key => $value) {
            if ($value !== '') {
                $regexp = '~^((COUNT\(DISTINCT |[A-Z0-9_]+\()(`(?:[^`]|``)+`|"(?:[^"]|"")+")\)|COUNT\(\*\))$~';
                $expression = $value;
                if (preg_match($regexp, $expression) === false) {
                    $expression = $this->driver->escapeId($expression);
                }
                if (isset($this->input->values['desc'][$key])) {
                    $expression .= ' DESC';
                }
                $expressions[] = $expression;
            }
        }
        return $expressions;
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

    /**
     * Apply SQL function
     *
     * @param string $function
     * @param string $column escaped column identifier
     *
     * @return string
     */
    public function applySqlFunction(string $function, string $column): string
    {
        if (!$function) {
            return $column;
        }
        if ($function === 'unixepoch') {
            return "DATETIME($column, '$function')";
        }
        if ($function === 'count distinct') {
            return "COUNT(DISTINCT $column)";
        }
        return strtoupper($function) . "($column)";
    }

    /**
     * @param array $value
     *
     * @return bool
     */
    private function colHasValidValue(array $value): bool
    {
        return $value['fun'] === 'count' ||
            ($value['col'] !== '' && (!$value['fun'] ||
                in_array($value['fun'], $this->driver->functions()) ||
                in_array($value['fun'], $this->driver->grouping())));
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
        foreach ((array) $this->input->values['columns'] as $key => $value) {
            if ($this->colHasValidValue($value)) {
                $fields = '*';
                if ($value['col'] !== '') {
                    $fields = $this->driver->escapeId($value['col']);
                }
                $select[$key] = $this->applySqlFunction($value['fun'], $fields);
                if (!in_array($value['fun'], $this->driver->grouping())) {
                    $group[] = $select[$key];
                }
            }
        }
        return [$select, $group];
    }

    /**
     * @param array $value
     * @param array $fields
     *
     * @return string
     */
    private function getWhereCondition(array $value, array $fields): string
    {
        $op = $value['op'];
        $val = $value['val'];
        $col = $value['col'];
        if (preg_match('~IN$~', $op)) {
            $in = $this->processLength($val);
            return " $op " . ($in !== '' ? $in : '(NULL)');
        }
        if ($op === 'SQL') {
            return ' ' . $val; // SQL injection
        }
        if ($op === 'LIKE %%') {
            return ' LIKE ' . $this->getUnconvertedFieldValue($fields[$col], "%$val%");
        }
        if ($op === 'ILIKE %%') {
            return ' ILIKE ' . $this->getUnconvertedFieldValue($fields[$col], "%$val%");
        }
        if ($op === 'FIND_IN_SET') {
            return ')';
        }
        if (!preg_match('~NULL$~', $op)) {
            return " $op " . $this->getUnconvertedFieldValue($fields[$col], $val);
        }
        return " $op";
    }

    /**
     * @param TableFieldEntity $field
     * @param array $value
     *
     * @return bool
     */
    private function selectFieldIsValid(TableFieldEntity $field, array $value): bool
    {
        $op = $value['op'];
        $val = $value['val'];
        $in = preg_match('~IN$~', $op) ? ',' : '';
        return (preg_match('~^[-\d.' . $in . ']+$~', $val) ||
                !preg_match('~' . $this->driver->numberRegex() . '|bit~', $field->type)) &&
            (!preg_match("~[\x80-\xFF]~", $val) ||
                preg_match('~char|text|enum|set~', $field->type)) &&
            (!preg_match('~date|timestamp~', $field->type) ||
                preg_match('~^\d+-\d+-\d+~', $val));
    }

    /**
     * @param array $value
     * @param array $fields
     *
     * @return string
     */
    private function getSelectExpression(array $value, array $fields): string
    {
        $op = $value['op'];
        $col = $value['col'];
        $prefix = '';
        if ($op === 'FIND_IN_SET') {
            $prefix = $op .'(' . $this->driver->quote($value['val']) . ', ';
        }
        $condition = $this->getWhereCondition($value, $fields);
        if ($col !== '') {
            return $prefix . $this->driver->convertSearch($this->driver->escapeId($col),
                $value, $fields[$col]) . $condition;
        }
        // find anywhere
        $clauses = [];
        foreach ($fields as $name => $field) {
            if ($this->selectFieldIsValid($field, $value)) {
                $clauses[] = $prefix . $this->driver->convertSearch($this->driver->escapeId($name),
                    $value, $field) . $condition;
            }
        }
        if (empty($clauses)) {
            return '1 = 0';
        }
        return '(' . implode(' OR ', $clauses) . ')';
    }

    /**
     * @param IndexEntity $index
     * @param int $i
     *
     * @return string
     */
    private function getMatchExpression(IndexEntity $index, int $i): string
    {
        $columns = array_map(function ($column) {
            return $this->driver->escapeId($column);
        }, $index->columns);
        $match = $this->driver->quote($this->input->values['fulltext'][$i]);
        if (isset($this->input->values['boolean'][$i])) {
            $match .= ' IN BOOLEAN MODE';
        }
        return 'MATCH (' . implode(', ', $columns) . ') AGAINST (' . $match . ')';
    }

    /**
     * Process search box in select
     *
     * @param array $fields
     * @param array $indexes
     *
     * @return array
     */
    public function processSelectWhere(array $fields, array $indexes): array
    {
        $expressions = [];
        foreach ($indexes as $i => $index) {
            if ($index->type === 'FULLTEXT' && $this->input->values['fulltext'][$i] !== '') {
                $expressions[] = $this->getMatchExpression($index, $i);
            }
        }
        foreach ((array) $this->input->values['where'] as $key => $value) {
            if (($value['col'] !== '' ||  $value['val'] !== '') &&
                in_array($value['op'], $this->driver->operators())) {
                $expressions[] = $this->getSelectExpression($value, $fields);
            }
        }
        return $expressions;
    }
}
