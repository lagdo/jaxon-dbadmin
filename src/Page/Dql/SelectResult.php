<?php

namespace Lagdo\DbAdmin\Db\Page\Dql;

use Lagdo\DbAdmin\Db\Page\AppPage;
use Lagdo\DbAdmin\Driver\DriverInterface;
use Lagdo\DbAdmin\Driver\Entity\TableFieldEntity;
use Lagdo\DbAdmin\Driver\Utils\Utils;

use function array_map;
use function compact;
use function current;
use function is_string;
use function key;
use function md5;
use function next;
use function preg_match;
use function strlen;
use function strpos;
use function trim;

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
     * @param SelectEntity $selectEntity
     * @param string $key
     * @param int $rank
     *
     * @return array
     */
    private function getResultHeaderItem(SelectEntity $selectEntity, string $key, int $rank): array
    {
        $valueKey = key($selectEntity->select);
        $value = $selectEntity->queryOptions["columns"][$valueKey] ?? [];

        $fun = $value["fun"] ?? '';
        $fieldKey = !$selectEntity->select ? $key :
            ($value["col"] ?? current($selectEntity->select));
        $field = $selectEntity->fields[$fieldKey];
        $name = !$field ? ($fun ? "*" : $key) :
            $this->page->fieldName($field, $rank);

        return [$fun, $name, $field];
    }

    /**
     * @param SelectEntity $selectEntity
     * @param string $key
     * @param mixed $value
     * @param int $rank
     *
     * @return array
     */
    private function getResultHeader(SelectEntity $selectEntity, string $key, $value, int $rank): array
    {
        if (isset($selectEntity->unselected[$key])) {
            return [];
        }

        [$fun, $name, $field] = $this->getResultHeaderItem($selectEntity, $key, $rank);
        $header = compact('field', 'name');
        if ($name != "") {
            $selectEntity->names[$key] = $name;
            // $href = remove_from_uri('(order|desc)[^=]*|page') . '&order%5B0%5D=' . urlencode($key);
            // $desc = "&desc%5B0%5D=1";
            $header['column'] = $this->driver->escapeId($key);
            // $header['key'] = $this->utils->str
            //     ->html($this->driver->bracketEscape($key));
            //! columns looking like functions
            $header['title'] = $this->page->applySqlFunction($fun, $name);
        }
        // $functions[$key] = $fun;
        next($selectEntity->select);

        return $header;
    }

    /**
     * Get the result headers from the first result row
     * @return void
     */
    public function setResultHeaders(SelectEntity $selectEntity): void
    {
        // Results headers
        $selectEntity->headers = [
            '', // !$group && $select ? '' : lang('Modify');
        ];
        $selectEntity->names = [];
        // $selectEntity->functions = [];
        reset($selectEntity->select);

        $rank = 1;
        $firstResultRow = $selectEntity->rows[0];
        foreach ($firstResultRow as $key => $value) {
            $header = $this->getResultHeader($selectEntity, $key, $value, $rank);
            if ($header['name'] ?? '' !== '') {
                $rank++;
            }
            $selectEntity->headers[] = $header;
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
                foreach($row as $key => $value)
                {
                    $lengths[$key] = \max($lengths[$key], \min(40, strlen(\utf8_decode($value))));
                }
            }
        }
        return $lengths;
    }*/

    /**
     * @param SelectEntity $selectEntity
     * @param array $row
     *
     * @return array
     */
    private function getUniqueIds(SelectEntity $selectEntity, array $row): array
    {
        $uniqueIds = $this->utils->uniqueIds($row, $selectEntity->indexes);
        if (empty($uniqueIds)) {
            $pattern = '~^(COUNT\((\*|(DISTINCT )?`(?:[^`]|``)+`)\)' .
                '|(AVG|GROUP_CONCAT|MAX|MIN|SUM)\(`(?:[^`]|``)+`\))$~';
            foreach ($row as $key => $value) {
                if (!preg_match($pattern, $key)) {
                    //! columns looking like functions
                    $uniqueIds[$key] = $value;
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
        $jush = $this->driver->jush();
        return ($jush === "sql" || $jush === "pgsql") &&
            is_string($value) && strlen($value) > 64 &&
            preg_match('~char|text|enum|set~', $type);
    }

    /**
     * @param string $key
     * @param string $collation
     *
     * @return string
     */
    private function getRowIdMd5Key(string $key, string $collation): string
    {
        return $this->driver->jush() !== 'sql' ||
            preg_match("~^utf8~", $collation) ? $key :
                "CONVERT($key USING " . $this->driver->charset() . ")";
    }

    /**
     * @param SelectEntity $selectEntity
     * @param string $key
     * @param mixed $value
     *
     * @return array
     */
    private function getRowIdValue(SelectEntity $selectEntity, string $key, $value): array
    {
        $key = trim($key);
        $type = '';
        $collation = '';
        if (isset($selectEntity->fields[$key])) {
            $type = $selectEntity->fields[$key]->type;
            $collation = $selectEntity->fields[$key]->collation;
        }
        if ($this->shouldEncodeRowId($type, $value)) {
            if (!strpos($key, '(')) {
                //! columns looking like functions
                $key = $this->driver->escapeId($key);
            }
            $key = "MD5(" . $this->getRowIdMd5Key($key, $collation) . ")";
            $value = md5($value);
        }
        return [$key, $value];
    }

    /**
     * @param SelectEntity $selectEntity
     * @param array $row
     *
     * @return array
     */
    public function getRowIds(SelectEntity $selectEntity, array $row): array
    {
        $uniqueIds = $this->getUniqueIds($selectEntity, $row);
        // Unique identifier to edit returned data.
        // $unique_idf = "";
        $rowIds = ['where' => [], 'null' => []];
        foreach ($uniqueIds as $key => $value) {
            [$key, $value] = $this->getRowIdValue($selectEntity, $key, $value);
            // $unique_idf .= "&" . ($value !== null ? \urlencode("where[" .
            // $this->driver->bracketEscape($key) . "]") . "=" .
            // \urlencode($value) : \urlencode("null[]") . "=" . \urlencode($key));
            if ($value === null) {
                $rowIds['null'][] = $this->driver->bracketEscape($key);
                continue;
            }
            $rowIds['where'][$this->driver->bracketEscape($key)] = $value;
        }
        return $rowIds;
    }

    /**
     * @param SelectEntity $selectEntity
     * @param string $key
     * @param mixed $value
     *
     * @return array
     */
    private function getColumnValue(SelectEntity $selectEntity, string $key, $value): array
    {
        $field = $selectEntity->fields[$key] ?? new TableFieldEntity();
        $textLength = $selectEntity->textLength;
        $value = $this->driver->value($value, $field);
        return $this->page->getFieldValue($field, $textLength, $value);
    }

    /**
     * @param SelectEntity $selectEntity
     * @param array $row
     *
     * @return array
     */
    private function getRowValues(SelectEntity $selectEntity, array $row): array
    {
        $cols = [];
        foreach ($row as $key => $value) {
            if (isset($selectEntity->names[$key])) {
                $cols[] = $this->getColumnValue($selectEntity, $key, $value);
            }
        }
        return $cols;
    }

    /**
     * @param SelectEntity $selectEntity
     *
     * @return array
     */
    public function getRows(SelectEntity $selectEntity): array
    {
        return array_map(fn($row) => [
            // Unique identifier to edit returned data.
            'ids' => $this->getRowIds($selectEntity, $row),
            'cols' => $this->getRowValues($selectEntity, $row),
        ], $selectEntity->rows);
    }
}
