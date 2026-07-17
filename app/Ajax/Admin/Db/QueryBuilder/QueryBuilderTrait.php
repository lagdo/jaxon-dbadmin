<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\QueryBuilder;

use Lagdo\DbAdmin\Support\Driver\UiDto\Dql\SelectDqDto;

use function count;
use function array_shift;

/**
 * This trait provides databag features for the Query Builder.
 */
trait QueryBuilderTrait
{
    /**
     * @var string
     */
    protected string $queryBuilderDatabag;

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return void
     */
    protected function setBuilderBag(string $key, $value): void
    {
        $this->setBag('dbadmin.builder', $key, $value);
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return mixed
     */
    protected function getBuilderBag(string $key, $value = null): mixed
    {
        return $this->getBag('dbadmin.builder', $key, $value);
    }

    /**
     * @param string $table
     *
     * @return void
     */
    protected function initBuilderParams(string $table): void
    {
        $this->setCurrentTable($table);

        $params = $this->getBuilderBag('params', []);
        // The table, columns, filters, sorting and foreigns values are reset,
        // while the options values are kept.
        $this->setBuilderBag('params', [[
            'limit' => 50,
            'total' => true,
            'length' => 100,
            ...($params[0] ?? []),
            'table' => $table,
            'page' => 1,
            'columns' => [],
            'filters' => [],
            'sorters' => [],
            'foreigns' => false,
        ]]);
    }

    /**
     * @param string $table
     * @param string $column
     * @param string|int $value
     *
     * @return void
     */
    protected function prependBuilderParams(string $table, string $column, string|int $value): void
    {
        $this->setCurrentTable($table);

        $params = $this->getBuilderBag('params', []);
        // Prepend a new entry into the params array.
        $this->setBuilderBag('params', [[
            'limit' => 50,
            'total' => true,
            'length' => 100,
            'table' => $table,
            'page' => 1,
            'columns' => [],
            'filters' => [[
                'column' => $column,
                'operator' => '=',
                'operand' => $value,
            ]],
            'sorters' => [],
            'foreigns' => false,
        ], ...$params]);
    }

    /**
     * @return bool
     */
    protected function removeBuilderParams(): bool
    {
        $params = $this->getBuilderBag('params', []);
        // There should be at least 2 entries in the params array.
        if (count($params) < 2) {
            return false;
        }

        // Remove the first entry in the params array.
        array_shift($params);

        // Back to the same page number on the previous table.
        $this->setCurrentTable($params[0]['table']);

        $this->setBuilderBag('params', $params);

        return true;
    }

    /**
     * @return array
     */
    protected function getBuilderParams(): array
    {
        $params = $this->getBuilderBag('params', []);

        // All the default values are overidden.
        return [
            'limit' => 50,
            'total' => true,
            'length' => 100,
            'columns' => [],
            'filters' => [],
            'sorters' => [],
            'foreigns' => false,
            ...($params[0] ?? []),
        ];
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    protected function getParamValue(string $name): mixed
    {
        return $this->getBuilderParams()[$name];
    }

    /**
     * @param string $name
     * @param mixed $value
     *
     * @return void
     */
    protected function saveParamValue(string $name, mixed $value): void
    {
        $params = $this->getBuilderBag('params', []);
        if (isset($params[0])) {
            $params[0][$name] = $value;
            $this->setBuilderBag('params', $params);
        }
    }

    /**
     * @param int $page
     *
     * @return void
     */
    protected function savePageNumber(int $page): void
    {
        $this->saveParamValue('page', $page);
    }

    /**
     * @return int
     */
    protected function getPageNumber(): int
    {
        return $this->getBuilderParams()['page'] ?? 1;
    }

    /**
     * @return SelectDqDto
     */
    protected function getSelectQueryParams(): SelectDqDto
    {
        $table = $this->getCurrentTable();
        $params = $this->getBuilderParams();

        return $this->driver()->getSelectParams($table, $params);
    }

    /**
     * @return string
     */
    protected function getBuilderSqlQuery(): string
    {
        return $this->getSelectQueryParams()->query();
    }

    /**
     * @return int
     */
    protected function countBuilderParams(): int
    {
        return count($this->getBuilderBag('params', []));
    }
}
