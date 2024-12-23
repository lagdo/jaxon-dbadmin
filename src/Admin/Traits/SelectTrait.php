<?php

namespace Lagdo\DbAdmin\Admin\Traits;

use function intval;
use function implode;
use function strtoupper;
use function in_array;
use function preg_match;
use function preg_replace;
use function preg_match_all;

trait SelectTrait
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
        $values = $this->input->values;
        $expressions = [];
        foreach ($values['order'] as $key => $value) {
            if ($value !== '') {
                $regexp = '~^((COUNT\(DISTINCT |[A-Z0-9_]+\()(`(?:[^`]|``)+`|"(?:[^"]|"")+")\)|COUNT\(\*\))$~';
                if (preg_match($regexp, $value) !== false) {
                    $value = $this->driver->escapeId($value);
                }
                if (isset($values['desc'][$key]) && intval($values['desc'][$key]) !== 0) {
                    $value .= ' DESC';
                }
                $expressions[] = $value;
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
        $values = $this->input->values;
        foreach ($values['columns'] as $key => $value) {
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
}
