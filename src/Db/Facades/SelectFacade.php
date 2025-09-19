<?php

namespace Lagdo\DbAdmin\Db\Facades;

use Lagdo\DbAdmin\Service\TimerService;
use Lagdo\DbAdmin\Db\Facades\Select\SelectEntity;
use Lagdo\DbAdmin\Db\Facades\Select\SelectQuery;
use Lagdo\DbAdmin\Driver\Entity\TableFieldEntity;
use Exception;

use function array_map;
use function compact;
use function count;
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
 * Facade to table select functions
 */
class SelectFacade extends AbstractFacade
{
    /**
     * @var SelectQuery
     */
    private SelectQuery $selectQuery;

    /**
     * @var SelectEntity|null
     */
    private SelectEntity|null $selectEntity = null;

    /**
     * @param AbstractFacade $dbFacade
     * @param TimerService $timer
     */
    public function __construct(AbstractFacade $dbFacade, protected TimerService $timer)
    {
        parent::__construct($dbFacade);

        $this->selectQuery = new SelectQuery($dbFacade);
    }

    /**
     * @param string $table The table name
     * @param array $queryOptions The query options
     *
     * @return void
     * @throws Exception
     */
    private function setSelectEntity(string $table, array $queryOptions = []): void
    {
        $tableStatus = $this->driver->tableStatusOrName($table);
        $tableName = $this->admin->tableName($tableStatus);
        $this->selectEntity = new SelectEntity($table,
            $tableName, $tableStatus, $queryOptions);
        $this->selectQuery->prepareSelect($this->selectEntity);
    }

    /**
     * Get required data for create/update on tables
     *
     * @param string $table The table name
     * @param array $queryOptions The query options
     *
     * @return SelectEntity
     * @throws Exception
     */
    public function getSelectData(string $table, array $queryOptions = []): SelectEntity
    {
        $this->setSelectEntity($table, $queryOptions);
        return $this->selectEntity;
    }

    /**
     * @return void
     */
    private function executeSelect(): void
    {
        // From driver.inc.php
        $this->timer->start();
        $statement = $this->driver->execute($this->selectEntity->query);
        $this->selectEntity->duration = $this->timer->duration();
        $this->selectEntity->rows = [];

        // From adminer.inc.php
        if (!$statement) {
            $this->selectEntity->error = $this->driver->error();
            return;
        }

        // From select.inc.php
        $this->selectEntity->rows = [];
        while (($row = $statement->fetchAssoc())) {
            if ($this->selectEntity->page && $this->driver->jush() == "oracle") {
                unset($row["RNUM"]);
            }
            $this->selectEntity->rows[] = $row;
        }
    }

    /**
     * @param string $key
     * @param int $rank
     *
     * @return array
     */
    private function getResultHeaderItem(string $key, int $rank): array
    {
        $valueKey = key($this->selectEntity->select);
        $value = $this->selectEntity->queryOptions["columns"][$valueKey] ?? [];

        $fun = $value["fun"] ?? '';
        $fieldKey = !$this->selectEntity->select ? $key :
            ($value["col"] ?? current($this->selectEntity->select));
        $field = $this->selectEntity->fields[$fieldKey];
        $name = !$field ? ($fun ? "*" : $key) :
            $this->admin->fieldName($field, $rank);

        return [$fun, $name, $field];
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param int $rank
     *
     * @return array
     */
    private function getResultHeader(string $key, $value, int $rank): array
    {
        if (isset($this->selectEntity->unselected[$key])) {
            return [];
        }

        [$fun, $name, $field] = $this->getResultHeaderItem($key, $rank);
        $header = compact('field', 'name');
        if ($name != "") {
            $this->selectEntity->names[$key] = $name;
            // $href = remove_from_uri('(order|desc)[^=]*|page') . '&order%5B0%5D=' . urlencode($key);
            // $desc = "&desc%5B0%5D=1";
            $header['column'] = $this->driver->escapeId($key);
            // $header['key'] = $this->utils->str
            //     ->html($this->driver->bracketEscape($key));
            //! columns looking like functions
            $header['title'] = $this->selectQuery->applySqlFunction($fun, $name);
        }
        // $functions[$key] = $fun;
        next($this->selectEntity->select);
        return $header;
    }

    /**
     * Get the result headers from the first result row
     * @return void
     */
    private function getResultHeaders(): void
    {
        // Results headers
        $this->selectEntity->headers = [
            '', // !$group && $select ? '' : lang('Modify');
        ];
        $this->selectEntity->names = [];
        // $this->selectEntity->functions = [];
        reset($this->selectEntity->select);

        $rank = 1;
        $firstResultRow = $this->selectEntity->rows[0];
        foreach ($firstResultRow as $key => $value) {
            $header = $this->getResultHeader($key, $value, $rank);
            if ($header['name'] ?? '' !== '') {
                $rank++;
            }
            $this->selectEntity->headers[] = $header;
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
     * @param array $row
     *
     * @return array
     */
    private function getUniqueIds(array $row): array
    {
        $uniqueIds = $this->admin->uniqueIds($row, $this->selectEntity->indexes);
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
        return $this->driver->jush() != 'sql' ||
            preg_match("~^utf8~", $collation) ? $key :
                "CONVERT($key USING " . $this->driver->charset() . ")";
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return array
     */
    private function getRowIdValue(string $key, $value): array
    {
        $key = trim($key);
        $type = '';
        $collation = '';
        if (isset($this->selectEntity->fields[$key])) {
            $type = $this->selectEntity->fields[$key]->type;
            $collation = $this->selectEntity->fields[$key]->collation;
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
     * @param array $row
     *
     * @return array
     */
    private function getRowIds(array $row): array
    {
        $uniqueIds = $this->getUniqueIds($row);
        // Unique identifier to edit returned data.
        // $unique_idf = "";
        $rowIds = ['where' => [], 'null' => []];
        foreach ($uniqueIds as $key => $value) {
            [$key, $value] = $this->getRowIdValue($key, $value);
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
     * @param string $key
     * @param mixed $value
     *
     * @return array
     */
    private function getRowColumn(string $key, $value): array
    {
        $field = $this->selectEntity->fields[$key] ?? new TableFieldEntity();
        $value = $this->driver->value($value, $field);
        /*if ($value != "" && (!isset($email_fields[$key]) || $email_fields[$key] != "")) {
            //! filled e-mails can be contained on other pages
            $email_fields[$key] = ($this->admin->isMail($value) ? $names[$key] : "");
        }*/
        $length = $this->selectEntity->textLength;
        $value = $this->admin->selectValue($field, $value, $length);
        return [
            // 'id',
            'text' => preg_match('~text|lob~', $field->type),
            'value' => $value,
            // 'editable' => false,
        ];
    }

    /**
     * @param array $row
     *
     * @return array
     */
    private function getRowColumns(array $row): array
    {
        $cols = [];
        foreach ($row as $key => $value) {
            if (isset($this->selectEntity->names[$key])) {
                $cols[] = $this->getRowColumn($key, $value);
            }
        }
        return $cols;
    }

    /**
     * @return bool
     */
    private function hasGroupsInFields(): bool
    {
        return count($this->selectEntity->group) <
            count($this->selectEntity->select);
    }

    /**
     * Get required data for create/update on tables
     *
     * @param string $table The table name
     * @param array $queryOptions The query options
     *
     * @return int
     */
    public function countSelect(string $table, array $queryOptions): int
    {
        $this->setSelectEntity($table, $queryOptions);

        try {
            $query = $this->driver->getRowCountQuery($table,
                $this->selectEntity->where, $this->hasGroupsInFields(),
                $this->selectEntity->group);
            return (int)$this->driver->result($query);
        } catch(Exception $_) {
            return -1;
        }
    }

    /**
     * Get required data for create/update on tables
     *
     * @param string $table The table name
     * @param array $queryOptions The query options
     *
     * @return array
     * @throws Exception
     */
    public function execSelect(string $table, array $queryOptions): array
    {
        $this->setSelectEntity($table, $queryOptions);

        $this->executeSelect();
        if ($this->selectEntity->error !== null) {
            return ['message' => $this->selectEntity->error];
        }
        if (!$this->selectEntity->rows) {
            return ['message' => $this->utils->trans->lang('No rows.')];
        }
        // $backward_keys = $this->driver->backwardKeys($table, $tableName);
        // lengths = $this->getValuesLengths($rows, $this->selectEntity->queryOptions);

        $this->getResultHeaders();

        return [
            'headers' => $this->selectEntity->headers,
            'query' => $this->selectEntity->query,
            'limit' => $this->selectEntity->limit,
            'duration' => $this->selectEntity->duration,
            'message' => null,
            'rows' => array_map(fn($row) => [
                // Unique identifier to edit returned data.
                'ids' => $this->getRowIds($row),
                'cols' => $this->getRowColumns($row),
            ], $this->selectEntity->rows),
        ];
    }
}
