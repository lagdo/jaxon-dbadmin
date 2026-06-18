<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto\Dql;

use Lagdo\DbAdmin\Driver\Sql\Connection\QueryResultInterface as QueryResult;
use Lagdo\DbAdmin\Driver\Sql\Dto\ColumnDto;
use Lagdo\DbAdmin\Support\Driver\AbstractDriverProxy;
use Lagdo\DbAdmin\Support\Driver\UiDto\RowsetDto;

use function array_map;
use function current;
use function is_string;
use function key;
use function md5;
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
     * @param SelectDqDto $input
     * @param string $columnName
     * @param int $position
     *
     * @return array
     */
    private function getResultHeaderItem(SelectDqDto $input, string $columnName, int $position): array
    {
        $valueKey = key($input->columns);
        $value = $input->params["columns"][$valueKey] ?? [];

        $fun = $value["fun"] ?? '';
        $columnKey = !$input->columns ? $columnName :
            ($value["col"] ?? current($input->columns));
        $column = $input->table->columns[$columnKey];
        $name = !$column ? ($fun ? "*" : $columnName) :
            $this->pageUi()->columnName($column, $position);

        return [$fun, $name, $column];
    }

    /**
     * @param SelectDqDto $input
     * @param string $columnName
     * @param int $position
     *
     * @return array
     */
    private function getResultHeader(SelectDqDto $input, string $columnName, int $position): array
    {
        if (isset($input->primaryColumns[$columnName])) {
            return [];
        }

        [$fun, $name, $column] = $this->getResultHeaderItem($input, $columnName, $position);
        $header = ['column' => $column, 'name' => $name];
        if ($name !== '') {
            // $href = remove_from_uri('(order|desc)[^=]*|page') . '&order%5B0%5D=' . urlencode($columnName);
            // $desc = "&desc%5B0%5D=1";
            $header['column'] = $this->statement()->escapeId($columnName);
            // $header['key'] = $this->utils()->html($this->statement()->bracketEscape($columnName));
            //! columns looking like functions
            $header['title'] = $this->pageUi()->applySqlFunction($fun, $name);
        }

        return $header;
    }

    /**
     * Get the result headers from the first result row
     *
     * @param SelectDqDto $input
     * @param array $queryColumns
     *
     * @return array
     */
    private function getResultHeaders(SelectDqDto $input, array $queryColumns): array
    {
        // Results headers
        $headers = [];
        // $functions = [];

        $position = 1;
        foreach ($queryColumns as $columnName) {
            $header = $this->getResultHeader($input, $columnName, $position);
            if ($header['name'] ?? '' !== '') {
                $position++;
            }
            $headers[$columnName] = $header;
        }

        return $headers;
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
     * @param SelectDqDto $input
     * @param array $row
     *
     * @return array
     */
    private function getUniqueIds(SelectDqDto $input, array $row): array
    {
        $uniqueIds = $this->utils()->uniqueIds($row, $input->table->indexes);
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
     * @param SelectDqDto $input
     * @param string $columnName
     * @param mixed $value
     *
     * @return mixed
     */
    private function getRowIdValue(SelectDqDto $input, string $columnName, $value): mixed
    {
        $type = '';
        $collation = '';
        if (isset($input->table->columns[$columnName])) {
            $type = $input->table->columns[$columnName]->type;
            $collation = $input->table->columns[$columnName]->collation;
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
     * @param SelectDqDto $input
     * @param array $row
     *
     * @return array
     */
    private function getRowIds(SelectDqDto $input, array $row): array
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
     * @param SelectDqDto $input
     * @param string $columnName
     * @param mixed $value
     *
     * @return array
     */
    private function getColumnValue(SelectDqDto $input, string $columnName, $value): array
    {
        $column = $input->table->columns[$columnName] ?? new ColumnDto();
        $textLength = $input->textLength;
        $value = $this->engine()->convertValue($value, $column);
        return $this->pageUi()->getColumnValue($column, $textLength, $value);
    }

    /**
     * @param SelectDqDto $input
     * @param array $headers
     * @param array $row
     *
     * @return array
     */
    private function getRowValues(SelectDqDto $input, array $headers, array $row): array
    {
        $cols = [];
        foreach ($row as $columnName => $value) {
            if (isset($headers[$columnName]['name'])) {
                $cols[] = $this->getColumnValue($input, $columnName, $value);
            }
        }
        return $cols;
    }

    /**
     * @param QueryResult $result
     * @param SelectDqDto $input
     *
     * @return RowsetDto
    */
    public function getSelectRowset(QueryResult $result, SelectDqDto $input): RowsetDto
    {
        if ($result->hasError()) {
            return new RowsetDto(error: $this->engine()->errorMessage());
        }

        $row = $result->fetchAssoc();
        if ($row === null) {
            return new RowsetDto(message: $this->utils()->lang('No rows.'));
        }

        $rowset = new RowsetDto();

        $rowset->headers = $this->getResultHeaders($input, array_keys($row));
        // $backward_keys = $this->engine()->backwardKeys($table, $tableName);
        // lengths = $this->getValuesLengths($rows, $input->queryOptions);

        do {
            if ($input->page && $this->engine()->oracle()) {
                unset($row["RNUM"]);
            }
            $rowset->rows[] = [
                // The unique identifiers to edit the result rows.
                'ids' => $this->getRowIds($input, $row),
                'cols' => $this->getRowValues($input, $rowset->headers, $row),
            ];

            $row = $result->fetchAssoc();
        } while ($row !== null);

        return $rowset;
    }

    /**
     * @param QueryResult $result
     * @param int $limit
     *
     * @return string
    */
    private function resultMessage(QueryResult $result, int $limit): string
    {
        $numRows = $result->rowCount();
        $message = '';
        if ($numRows > 0) {
            if ($limit > 0 && $numRows > $limit) {
                $message = $this->utils()->lang('%d / ', $limit);
            }
            $message .= $this->utils()->lang('%d row(s)', $numRows);
        }
        return $message;
    }

    /**
     * @param mixed $value
     * @param int $column
     * @param array $blobs
     * @param array $types
     *
     * @return string
    */
    private function columValue(mixed $value, int $column, array $blobs, array $types): string
    {
        // $link = $this->editLink($val);
        return match(true) {
            $value === null => '<i>NULL</i>',
            //! link to download
            isset($blobs[$column]) && $blobs[$column] && !$this->utils()->str->isUtf8($value) =>
                '<i>' . $this->utils()->lang('%d byte(s)', strlen($value)) . '</i>',
            isset($types[$column]) && $types[$column] === 254 =>
                '<code>' . $this->utils()->html($value) . '</code>',
            default => $this->utils()->html($value),
        };
    }

    /**
     * @param QueryResult $result
     * @param int $count
     * @param int $limit
     *
     * @return array|null
     */
    private function fetchRow(QueryResult $result, int $count, int $limit): array|null
    {
        return ($limit > 0 && $count >= $limit) ? null : $result->fetchRow();
    }

    /**
     * @param QueryResult $result
     * @param int $limit
     *
     * @return RowsetDto
    */
    public function getQueryRowset(QueryResult $result, int $limit): RowsetDto
    {
        if ($result->hasError()) {
            return new RowsetDto(error: $this->engine()->errorMessage());
        }

        // No rowset
        if (!$result->hasRowset()) {
            $affectedRows = $this->engine()->affectedRows();
            $message = 'Query executed OK, %d row(s) affected.';
            $message = $this->utils()->lang($message, $affectedRows); //  . "$time";
            $rowset = new RowsetDto(message: $message);
            $rowset->affectedRows = $affectedRows;

            return $rowset;
        }

        // Fetch the first row.
        $row = $this->fetchRow($result, 0, $limit);
        if ($row === null) {
            return new RowsetDto(message: $this->utils()->lang('No rows.'));
        }

        $rowset = new RowsetDto(message: $this->resultMessage($result, $limit));

        // Use the first row to get the table headers.
        $blobs = []; // colno => bool - display bytes for blobs
        $types = []; // colno => type - display char in <code>
        // Important: the values in the row are actually not used.
        $columns = array_map(fn($_) => $result->fetchColumn(), $row);
        foreach ($columns as $column) {
            $rowset->headers[] = $this->utils()->html($column->name());
            // table => orgtable - mapping to use in EXPLAIN
            $rowset->tables[$column->tableName()] = $column->orgTable();

            // $this->indexes($column);
            $blobs[] = $column->isBinary();
            $types[] = $column->type(); // Some drivers don't set the type field.
        }

        // Table rows (the first was already fetched).
        $column = 0;
        $callback = fn($value) => $this->columValue($value, $column++, $blobs, $types);
        do {
            $rowset->rowCount++;
            $rowset->rows[] = array_map($callback, $row);

            $row = $this->fetchRow($result, $rowset->rowCount, $limit);
        } while ($row !== null);

        return $rowset;
    }
}
