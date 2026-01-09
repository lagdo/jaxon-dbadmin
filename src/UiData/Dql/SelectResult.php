<?php

namespace Lagdo\DbAdmin\Db\UiData\Dql;

use Lagdo\DbAdmin\Db\UiData\AppPage;
use Lagdo\DbAdmin\Driver\DriverInterface;
use Lagdo\DbAdmin\Driver\Dto\TableFieldDto;
use Lagdo\DbAdmin\Driver\Utils\Utils;

use function array_map;
use function current;
use function in_array;
use function is_string;
use function key;
use function md5;
use function next;
use function preg_match;
use function strlen;
use function strpos;
use function trim;

/**
 * Prepare the results of a select query for the frontend.
 */
class SelectResult
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
     * @param SelectDto $selectDto
     * @param string $column
     * @param int $position
     *
     * @return array
     */
    private function getResultHeaderItem(SelectDto $selectDto, string $column, int $position): array
    {
        $valueKey = key($selectDto->select);
        $value = $selectDto->queryOptions["columns"][$valueKey] ?? [];

        $fun = $value["fun"] ?? '';
        $fieldKey = !$selectDto->select ? $column :
            ($value["col"] ?? current($selectDto->select));
        $field = $selectDto->fields[$fieldKey];
        $name = !$field ? ($fun ? "*" : $column) : $this->page->fieldName($field, $position);

        return [$fun, $name, $field];
    }

    /**
     * @param SelectDto $selectDto
     * @param string $column
     * @param int $position
     *
     * @return array
     */
    private function getResultHeader(SelectDto $selectDto, string $column, int $position): array
    {
        if (isset($selectDto->unselected[$column])) {
            return [];
        }

        [$fun, $name, $field] = $this->getResultHeaderItem($selectDto, $column, $position);
        $header = ['field' => $field, 'name' => $name];
        if ($name != "") {
            $selectDto->names[$column] = $name;
            // $href = remove_from_uri('(order|desc)[^=]*|page') . '&order%5B0%5D=' . urlencode($column);
            // $desc = "&desc%5B0%5D=1";
            $header['column'] = $this->driver->escapeId($column);
            // $header['key'] = $this->utils->html($this->driver->bracketEscape($column));
            //! columns looking like functions
            $header['title'] = $this->page->applySqlFunction($fun, $name);
        }
        // $functions[$column] = $fun;
        next($selectDto->select);

        return $header;
    }

    /**
     * Get the result headers from the first result row
     *
     * @param SelectDto $selectDto
     * @param array $queryFields
     *
     * @return void
     */
    public function setResultHeaders(SelectDto $selectDto, array $queryFields): void
    {
        // Results headers
        $selectDto->headers = [];
        $selectDto->names = [];
        // $selectDto->functions = [];
        reset($selectDto->select);

        $position = 1;
        foreach ($queryFields as $column) {
            $header = $this->getResultHeader($selectDto, $column, $position);
            if ($header['name'] ?? '' !== '') {
                $position++;
            }
            $selectDto->headers[] = $header;
        }
    }

    /**
     * @param array $rows
     * @param array $queryOptions
     *
     * @return array
     */
    /*private function getValuesLengths(array $rows, array $queryOptions): array
    {
        $lengths = [];
        if($queryOptions["modify"])
        {
            foreach($rows as $row)
            {
                foreach($row as $column => $value)
                {
                    $lengths[$column] = \max($lengths[$column], \min(40, strlen(\utf8_decode($value))));
                }
            }
        }
        return $lengths;
    }*/

    /**
     * @param SelectDto $selectDto
     * @param array $row
     *
     * @return array
     */
    private function getUniqueIds(SelectDto $selectDto, array $row): array
    {
        $uniqueIds = $this->utils->uniqueIds($row, $selectDto->indexes);
        if (empty($uniqueIds)) {
            $pattern = '~^(COUNT\((\*|(DISTINCT )?`(?:[^`]|``)+`)\)' .
                '|(AVG|GROUP_CONCAT|MAX|MIN|SUM)\(`(?:[^`]|``)+`\))$~';
            foreach ($row as $column => $value) {
                if (!preg_match($pattern, $column)) {
                    //! columns looking like functions
                    $uniqueIds[$column] = $value;
                }
            }
        }
        return $uniqueIds;
    }

    /**
     * @param string $type
     * @param mixed $value
     *
     * @return bool
     */
    private function shouldEncodeRowId(string $type, $value): bool
    {
        return in_array($this->driver->jush(), ['sql', 'pgsql']) &&
            is_string($value) && strlen($value) > 64 &&
            preg_match('~char|text|enum|set~', $type);
    }

    /**
     * @param string $column
     * @param string $collation
     *
     * @return string
     */
    private function getRowIdMd5Key(string $column, string $collation): string
    {
        return $this->driver->jush() !== 'sql' ||
            preg_match("~^utf8~", $collation) ? $column :
                "CONVERT($column USING " . $this->driver->charset() . ")";
    }

    /**
     * @param SelectDto $selectDto
     * @param string $column
     * @param mixed $value
     *
     * @return mixed
     */
    private function getRowIdValue(SelectDto $selectDto, string $column, $value): mixed
    {
        $type = '';
        $collation = '';
        if (isset($selectDto->fields[$column])) {
            $type = $selectDto->fields[$column]->type;
            $collation = $selectDto->fields[$column]->collation;
        }
        if ($this->shouldEncodeRowId($type, $value)) {
            if (!strpos($column, '(')) {
                //! columns looking like functions
                $column = $this->driver->escapeId($column);
            }
            // Set the value to an array to indicate that a function is applied to the column.
            $expr = "MD5(" . $this->getRowIdMd5Key($column, $collation) . ")";
            $value = [
                'expr' => $this->driver->bracketEscape($expr),
                'value' => md5($value),
            ];
        }
        return $value;
    }

    /**
     * @param SelectDto $selectDto
     * @param array $row
     *
     * @return array
     */
    public function getRowIds(SelectDto $selectDto, array $row): array
    {
        $uniqueIds = $this->getUniqueIds($selectDto, $row);
        // Unique identifier to edit returned data.
        // $unique_idf = "";
        $rowIds = ['where' => [], 'null' => []];
        foreach ($uniqueIds as $column => $value) {
            $column = trim($column);
            $value = $this->getRowIdValue($selectDto, $column, $value);
            $column = $this->driver->bracketEscape($column);

            // $unique_idf .= "&" . ($value !== null ? \urlencode("where[" .
            // $column . "]") . "=" .
            // \urlencode($value) : \urlencode("null[]") . "=" . \urlencode($column));
            if ($value === null) {
                $rowIds['null'][] = $column;
                continue;
            }
            $rowIds['where'][$column] = $value;
        }
        return $rowIds;
    }

    /**
     * @param SelectDto $selectDto
     * @param string $column
     * @param mixed $value
     *
     * @return array
     */
    private function getColumnValue(SelectDto $selectDto, string $column, $value): array
    {
        $field = $selectDto->fields[$column] ?? new TableFieldDto();
        $textLength = $selectDto->textLength;
        $value = $this->driver->value($value, $field);
        return $this->page->getFieldValue($field, $textLength, $value);
    }

    /**
     * @param SelectDto $selectDto
     * @param array $row
     *
     * @return array
     */
    private function getRowValues(SelectDto $selectDto, array $row): array
    {
        $cols = [];
        foreach ($row as $column => $value) {
            if (isset($selectDto->names[$column])) {
                $cols[] = $this->getColumnValue($selectDto, $column, $value);
            }
        }
        return $cols;
    }

    /**
     * @param SelectDto $selectDto
     *
     * @return array
     */
    public function getRows(SelectDto $selectDto): array
    {
        return array_map(fn($row) => [
            // The unique identifiers to edit the result rows.
            'ids' => $this->getRowIds($selectDto, $row),
            'cols' => $this->getRowValues($selectDto, $row),
        ], $selectDto->rows);
    }
}
