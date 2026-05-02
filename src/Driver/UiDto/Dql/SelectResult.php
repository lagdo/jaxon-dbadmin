<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto\Dql;

use Lagdo\DbAdmin\Support\Driver\AbstractDriverProxy;
use Lagdo\DbAdmin\Driver\Sql\Dto\ColumnDto;

use function array_map;
use function current;
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
class SelectResult extends AbstractDriverProxy
{
    /**
     * @param DqInputDto $input
     * @param string $columnName
     * @param int $position
     *
     * @return array
     */
    private function getResultHeaderItem(DqInputDto $input, string $columnName, int $position): array
    {
        $valueKey = key($input->clauses);
        $value = $input->queryOptions["columns"][$valueKey] ?? [];

        $fun = $value["fun"] ?? '';
        $columnKey = !$input->clauses ? $columnName :
            ($value["col"] ?? current($input->clauses));
        $column = $input->columns[$columnKey];
        $name = !$column ? ($fun ? "*" : $columnName) :
            $this->pageUi()->columnName($column, $position);

        return [$fun, $name, $column];
    }

    /**
     * @param DqInputDto $input
     * @param string $columnName
     * @param int $position
     *
     * @return array
     */
    private function getResultHeader(DqInputDto $input, string $columnName, int $position): array
    {
        if (isset($input->unselected[$columnName])) {
            return [];
        }

        [$fun, $name, $column] = $this->getResultHeaderItem($input, $columnName, $position);
        $header = ['column' => $column, 'name' => $name];
        if ($name != "") {
            $input->names[$columnName] = $name;
            // $href = remove_from_uri('(order|desc)[^=]*|page') . '&order%5B0%5D=' . urlencode($columnName);
            // $desc = "&desc%5B0%5D=1";
            $header['column'] = $this->statement()->escapeId($columnName);
            // $header['key'] = $this->utils()->html($this->statement()->bracketEscape($columnName));
            //! columns looking like functions
            $header['title'] = $this->pageUi()->applySqlFunction($fun, $name);
        }
        // $functions[$columnName] = $fun;
        next($input->clauses);

        return $header;
    }

    /**
     * Get the result headers from the first result row
     *
     * @param DqInputDto $input
     * @param array $queryColumns
     *
     * @return void
     */
    public function setResultHeaders(DqInputDto $input, array $queryColumns): void
    {
        // Results headers
        $input->headers = [];
        $input->names = [];
        // $input->functions = [];
        reset($input->clauses);

        $position = 1;
        foreach ($queryColumns as $columnName) {
            $header = $this->getResultHeader($input, $columnName, $position);
            if ($header['name'] ?? '' !== '') {
                $position++;
            }
            $input->headers[] = $header;
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
                foreach($row as $columnName => $value)
                {
                    $lengths[$columnName] = \max($lengths[$columnName], \min(40, strlen(\utf8_decode($value))));
                }
            }
        }
        return $lengths;
    }*/

    /**
     * @param DqInputDto $input
     * @param array $row
     *
     * @return array
     */
    private function getUniqueIds(DqInputDto $input, array $row): array
    {
        $uniqueIds = $this->utils()->uniqueIds($row, $input->indexes);
        if (empty($uniqueIds)) {
            $pattern = '~^(COUNT\((\*|(DISTINCT )?`(?:[^`]|``)+`)\)' .
                '|(AVG|GROUP_CONCAT|MAX|MIN|SUM)\(`(?:[^`]|``)+`\))$~';
            foreach ($row as $columnName => $value) {
                if (!preg_match($pattern, $columnName)) {
                    //! columns looking like functions
                    $uniqueIds[$columnName] = $value;
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
        return ($this->engine()->sql() || $this->engine()->pgsql()) &&
            is_string($value) && strlen($value) > 64 &&
            preg_match('~char|text|enum|set~', $type);
    }

    /**
     * @param string $columnName
     * @param string $collation
     *
     * @return string
     */
    private function getRowIdMd5Key(string $columnName, string $collation): string
    {
        return !$this->engine()->sql() || preg_match("~^utf8~", $collation) ?
            $columnName : "CONVERT($columnName USING " . $this->engine()->charset() . ")";
    }

    /**
     * @param DqInputDto $input
     * @param string $columnName
     * @param mixed $value
     *
     * @return mixed
     */
    private function getRowIdValue(DqInputDto $input, string $columnName, $value): mixed
    {
        $type = '';
        $collation = '';
        if (isset($input->columns[$columnName])) {
            $type = $input->columns[$columnName]->type;
            $collation = $input->columns[$columnName]->collation;
        }
        if ($this->shouldEncodeRowId($type, $value)) {
            if (!strpos($columnName, '(')) {
                //! columns looking like functions
                $columnName = $this->statement()->escapeId($columnName);
            }
            // Set the value to an array to indicate that a function is applied to the column.
            $expr = "MD5(" . $this->getRowIdMd5Key($columnName, $collation) . ")";
            $value = [
                'expr' => $this->statement()->bracketEscape($expr),
                'value' => md5($value),
            ];
        }
        return $value;
    }

    /**
     * @param DqInputDto $input
     * @param array $row
     *
     * @return array
     */
    public function getRowIds(DqInputDto $input, array $row): array
    {
        $uniqueIds = $this->getUniqueIds($input, $row);
        // Unique identifier to edit returned data.
        // $unique_idf = "";
        $rowIds = ['where' => [], 'null' => []];
        foreach ($uniqueIds as $columnName => $value) {
            $columnName = trim($columnName);
            $value = $this->getRowIdValue($input, $columnName, $value);
            $columnName = $this->statement()->bracketEscape($columnName);

            // $unique_idf .= "&" . ($value !== null ? \urlencode("where[" .
            // $columnName . "]") . "=" .
            // \urlencode($value) : \urlencode("null[]") . "=" . \urlencode($columnName));
            if ($value === null) {
                $rowIds['null'][] = $columnName;
                continue;
            }
            $rowIds['where'][$columnName] = $value;
        }
        return $rowIds;
    }

    /**
     * @param DqInputDto $input
     * @param string $columnName
     * @param mixed $value
     *
     * @return array
     */
    private function getColumnValue(DqInputDto $input, string $columnName, $value): array
    {
        $column = $input->columns[$columnName] ?? new ColumnDto();
        $textLength = $input->textLength;
        $value = $this->engine()->convertValue($value, $column);
        return $this->pageUi()->getColumnValue($column, $textLength, $value);
    }

    /**
     * @param DqInputDto $input
     * @param array $row
     *
     * @return array
     */
    private function getRowValues(DqInputDto $input, array $row): array
    {
        $cols = [];
        foreach ($row as $columnName => $value) {
            if (isset($input->names[$columnName])) {
                $cols[] = $this->getColumnValue($input, $columnName, $value);
            }
        }
        return $cols;
    }

    /**
     * @param DqInputDto $input
     *
     * @return array
     */
    public function getRows(DqInputDto $input): array
    {
        return array_map(fn(array $row) => [
            // The unique identifiers to edit the result rows.
            'ids' => $this->getRowIds($input, $row),
            'cols' => $this->getRowValues($input, $row),
        ], $input->rows);
    }
}
