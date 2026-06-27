<?php

namespace Lagdo\DbAdmin\App\Ajax\Admin\Db\QueryBuilder;

use Lagdo\DbAdmin\Support\Driver\UiDto\Dql\SelectDqDto;

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
     * @return void
     */
    protected function newBuilderBag(string $key, $value): void
    {
        $this->newBag('dbadmin.builder', $key, $value);
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
     * @return void
     */
    protected function initBuilderParams(): void
    {
        $params = $this->getBuilderBag('params', []);
        // The table, columns, filters and sorting values are reset,
        // while the options values are kept.
        $this->setBuilderBag('params', [[
            'limit' => 50,
            'total' => true,
            'length' => 100,
            ...($params[0] ?? []),
            'table' => $this->getCurrentTable(),
            'columns' => [],
            'filters' => [],
            'sorters' => [],
        ]]);
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
        // The table, columns, filters and sorting values are reset,
        // while the options values are kept.
        $this->setBuilderBag('params', [[
            ...($params[0] ?? []),
            $name => $value,
        ]]);
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
     * @return SelectDqDto
     */
    protected function getSelectQueryParams(): SelectDqDto
    {
        $table = $this->getCurrentTable();
        $params = $this->getBuilderParams();

        return $this->db()->getSelectParams($table, $params);
    }

    /**
     * @return string
     */
    protected function getBuilderSqlQuery(): string
    {
        return $this->getSelectQueryParams()->query;
    }
}
