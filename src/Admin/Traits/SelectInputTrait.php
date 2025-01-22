<?php

namespace Lagdo\DbAdmin\Admin\Traits;

use Lagdo\DbAdmin\Driver\Entity\IndexEntity;
use Lagdo\DbAdmin\Driver\Entity\TableFieldEntity;

use function preg_match;
use function implode;
use function in_array;

trait SelectInputTrait
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
            $in = $this->driver->processLength($val);
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
        $match = $this->driver->quote($this->utils->input->values['fulltext'][$i]);
        if (isset($this->utils->input->values['boolean'][$i])) {
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
            if ($index->type === 'FULLTEXT' && $this->utils->input->values['fulltext'][$i] !== '') {
                $expressions[] = $this->getMatchExpression($index, $i);
            }
        }
        foreach ((array) $this->utils->input->values['where'] as $value) {
            if (($value['col'] !== '' ||  $value['val'] !== '') &&
                in_array($value['op'], $this->driver->operators())) {
                $expressions[] = $this->getSelectExpression($value, $fields);
            }
        }
        return $expressions;
    }
}
