<?php

namespace Lagdo\DbAdmin\Support\Driver\UiDto\Dql;

use Lagdo\DbAdmin\Driver\Sql\Connection\QueryResultInterface as QueryResult;
use Lagdo\DbAdmin\Driver\Sql\Dto\ColumnDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\ForeignKeyDto;
use Lagdo\DbAdmin\Driver\Sql\Dto\SelectColumnDto;
use Lagdo\DbAdmin\Support\Driver\AbstractDriverProxy;

use function array_combine;
use function array_filter;
use function array_keys;
use function array_map;
use function array_values;
use function count;
use function is_string;
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
     * @param SelectDqDto $select
     * @param array $row
     *
     * @return array
     */
    private function getUniqueIds(SelectDqDto $select, array $row): array
    {
        $uniqueIds = $this->utils()->uniqueIds($row, $select->table->indexes);
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
     * @param SelectDqDto $select
     * @param string $columnName
     * @param mixed $value
     *
     * @return mixed
     */
    private function getRowIdValue(SelectDqDto $select, string $columnName, $value): mixed
    {
        $type = '';
        $collation = '';
        if (isset($select->table->columns[$columnName])) {
            $type = $select->table->columns[$columnName]->type;
            $collation = $select->table->columns[$columnName]->collation;
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
     * @param SelectDqDto $select
     * @param array $row
     *
     * @return array
     */
    private function getRowIds(SelectDqDto $select, array $row): array
    {
        if ($select->grouped) {
            return [null, null];
        }

        $uniqueIds = $this->getUniqueIds($select, $row);
        if (count($uniqueIds) === 0) {
            return [null, null];
        }

        // Unique identifier to edit returned data.
        // $unique_idf = "";
        $editValues = null;
        $nullValues = null;
        foreach ($uniqueIds as $columnName => $value) {
            $columnName = trim($columnName);
            $value = $this->getRowIdValue($select, $columnName, $value);
            $columnName = $this->statement()->bracketEscape($columnName);

            // $unique_idf .= "&" . ($value !== null ? \urlencode("where[" .
            // $columnName . "]") . "=" .
            // \urlencode($value) : \urlencode("null[]") . "=" . \urlencode($columnName));
            if ($value === null) {
                $nullValues ??= [];
                $nullValues[] = $columnName;
                continue;
            }

            $editValues ??= [];
            $editValues[$columnName] = $value;
        }

        return [$editValues, $nullValues];
    }

    /**
     * @param SelectDqDto $select
     * @param ColumnDto $column
     *
     * @return ForeignKeyDto|null
     */
    private function getColumnForeignKey(SelectDqDto $select, ColumnDto $column): ForeignKeyDto|null
    {
        $filter = fn(ForeignKeyDto $key) =>
            count($key->source) === 1 && $key->source[0] === $column->name;
        $foreignKeys = array_filter($select->table->foreignKeys, $filter);

        return (array_values($foreignKeys))[0] ?? null;
    }

    /**
     * @param SelectDqDto $select
     * @param string $dbField
     * @param string|null $sqlField
     * @param SelectColumnDto|null $uiColumn
     *
     * @return QueryResultHeaderDto
     */
    private function getResultHeader(SelectDqDto $select, string $dbField,
        string|null $sqlField, SelectColumnDto|null $uiColumn): QueryResultHeaderDto
    {
        // if (isset($select->primaryColumns[$dbField])) {
        //     return [];
        // }

        $header = new QueryResultHeaderDto();

        $function = $uiColumn?->func ?? '';
        $columnKey = !$select->columns ? $dbField : ($uiColumn?->columnName ?? $sqlField);
        $column = $select->table->columns[$columnKey] ?? null;
        if ($column === null) {
            $header->column = new ColumnDto();
            // The SQL clause in the select query.
            $header->field = $function ? '*' : $dbField;
        } else {
            $header->column = $column;
            $header->foreignKey = $this->getColumnForeignKey($select, $column);
            // The SQL clause in the select query.
            $header->field = $this->pageUi()->columnName($column);
        }

        if ($header->field === '') {
            return $header;
        }

        // $href = remove_from_uri('(order|desc)[^=]*|page') . '&order%5B0%5D=' . urlencode($dbField);
        // $desc = '&desc%5B0%5D=1';
        // 'key' => $this->utils()->html($this->statement()->bracketEscape($dbField)),
        $header->title = $this->pageUi()->applySqlFunction($function, $header->field);
        return $header;
    }

    /**
     * Get the result headers from the first result row
     *
     * @param SelectDqDto $select
     * @param array $fields
     *
     * @return array
     */
    private function getResultHeaders(SelectDqDto $select, array $fields): array
    {
        $builder = fn($dbField, $sqlField, $uiColumn) =>
            $this->getResultHeader($select, $dbField, $sqlField, $uiColumn);
        $headers = array_map($builder, $fields, $select->columns, $select->input->columns);

        return array_combine($fields, $headers);
    }

    /**
     * @param SelectDqDto $select
     * @param QueryResultHeaderDto $header
     * @param mixed $value
     *
     * @return array
     */
    private function getColumnValue(SelectDqDto $select, QueryResultHeaderDto $header, $value): array
    {
        $value = $this->engine()->convertValue($value, $header->column);
        $textLength = $select->input->textLength;

        $value = $this->pageUi()->getColumnValue($header->column, $textLength, $value);
        $value['foreign'] = $header->foreignKey;

        return $value;
    }

    /**
     * @param SelectDqDto $select
     * @param array<QueryResultHeaderDto> $headers
     * @param array $values
     *
     * @return array
     */
    private function getRowValues(SelectDqDto $select, array $headers, array $values): array
    {
        // Unlike Adminer, we have only SQL query results.
        // So all the rows have the same columns => no need to filter the values.
        // $filter = fn(string $columnName) => isset($headers[$columnName]);
        // $values = array_filter($values, $filter, ARRAY_FILTER_USE_KEY);
        $callback = fn($value, string $columnName) =>
            $this->getColumnValue($select, $headers[$columnName], $value);

        return array_map($callback, $values, array_keys($values));
    }

    /**
     * @param QueryResult $result
     * @param SelectDqDto $select
     *
     * @return SelectRowsetDto
    */
    public function getSelectRowset(QueryResult $result, SelectDqDto $select): SelectRowsetDto
    {
        if ($result->hasError()) {
            return new SelectRowsetDto(error: $this->engine()->errorMessage());
        }

        // Fetch the first result row.
        $values = $result->fetchAssoc();
        if ($values === null) {
            return new SelectRowsetDto(message: $this->utils()->lang('No rows.'));
        }

        $rowset = new SelectRowsetDto();
        $rowset->headers = $this->getResultHeaders($select, array_keys($values));
        // $backward_keys = $this->engine()->backwardKeys($table, $tableName);
        // lengths = $this->getValuesLengths($rows, $select->queryOptions);

        // Process the result rows. The first is already fetched.
        do {
            // if ($select->input->page && $this->engine()->oracle()) {
            //     unset($row["RNUM"]);
            // }
            $row = new QueryResultRowDto();
            // The unique identifiers to edit the result rows.
            [$row->editValues, $row->nullValues] = $this->getRowIds($select, $values);
            $row->columns = $this->getRowValues($select, $rowset->headers, $values);

            $rowset->rows[] = $row;
        } while (($values = $result->fetchAssoc()) !== null);

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
     * @return QueryRowsetDto
    */
    public function getQueryRowset(QueryResult $result, int $limit): QueryRowsetDto
    {
        if ($result->hasError()) {
            return new QueryRowsetDto(error: $this->engine()->errorMessage());
        }

        // No rowset
        if (!$result->hasRowset()) {
            $affectedRows = $this->engine()->affectedRows();
            $message = 'Query executed OK, %d row(s) affected.';
            $message = $this->utils()->lang($message, $affectedRows); //  . "$time";
            $rowset = new QueryRowsetDto(message: $message);
            $rowset->affectedRows = $affectedRows;

            return $rowset;
        }

        // Fetch the first row.
        $row = $this->fetchRow($result, 0, $limit);
        if ($row === null) {
            return new QueryRowsetDto(message: $this->utils()->lang('No rows.'));
        }

        $rowset = new QueryRowsetDto(message: $this->resultMessage($result, $limit));

        // Use the first row to get the table headers.
        $blobs = []; // colno => bool - display bytes for blobs
        $types = []; // colno => type - display char in <code>
        // Important: the values in the row are actually not used.
        $columns = array_map(fn($_) => $result->fetchColumn(), $row);
        foreach ($columns as $column) {
            $rowset->headers[] = $this->utils()->html($column->name());
            // table => orgtable - mapping to use in EXPLAIN
            // $rowset->tables[$column->tableName()] = $column->orgTable();

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
        } while (($this->fetchRow($result, $rowset->rowCount, $limit)) !== null);

        return $rowset;
    }
}
