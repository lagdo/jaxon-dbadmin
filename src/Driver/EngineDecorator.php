<?php

namespace Lagdo\DbAdmin\Support\Driver;

use Lagdo\DbAdmin\Driver\AbstractEngine;
use Lagdo\DbAdmin\Driver\AbstractStatement;
use Lagdo\DbAdmin\Driver\Sql\Config\DriverConfig;
use Lagdo\DbAdmin\Driver\Sql\Connection\QueryResultInterface;
use Lagdo\DbAdmin\Driver\Sql\Specific\Engine\AbstractDatabase;
use Lagdo\DbAdmin\Driver\Sql\Specific\Engine\AbstractQuery;
use Lagdo\DbAdmin\Driver\Sql\Specific\Engine\AbstractServer;
use Lagdo\DbAdmin\Driver\Sql\Specific\Engine\AbstractTable;
use Lagdo\DbAdmin\Driver\Utils\Utils;
use Closure;

/**
 * Add callbacks to the engine features, using the decorator pattern.
 */
class EngineDecorator extends AbstractEngine
{
    /**
     * @var array<QueryCallback>
     */
    private array $callbacks = [];

    /**
     * @param AbstractEngine $engine
     */
    public function __construct(protected AbstractEngine $engine)
    {}

    /**
     * @inheritDoc
     */
    public function name(): string
    {
        return $this->engine->name();
    }

    /**
     * @return DriverConfig
     */
    protected function config(): DriverConfig
    {
        return $this->engine->config();
    }

    /**
     * @return Utils
     */
    protected function _utils(): Utils
    {
        return $this->engine->_utils();
    }

    /**
     * @return AbstractStatement
     */
    protected function _statement(): AbstractStatement
    {
        return $this->engine->_statement();
    }

    /**
     * @return AbstractServer
     */
    protected function _server(): AbstractServer
    {
        return $this->engine->_server();
    }

    /**
     * @return AbstractDatabase
     */
    protected function _database(): AbstractDatabase
    {
        return $this->engine->_database();
    }

    /**
     * @return AbstractTable
     */
    protected function _table(): AbstractTable
    {
        return $this->engine->_table();
    }

    /**
     * @return AbstractQuery
     */
    protected function _query(): AbstractQuery
    {
        return $this->engine->_query();
    }

    /**
     * @param QueryCallback $callback
     *
     * @return void
     */
    public function addQueryCallback(QueryCallback $callback): void
    {
        $this->callbacks[] = $callback;
    }

    /**
     * @param string $query
     * @param Closure $func
     *
     * @return mixed
     */
    private function execWithCallbacks(string $query, Closure $func): mixed
    {
        foreach ($this->callbacks as $callback) {
            $callback->beforeQueryExec($query);
        }
        $result = $func($this->engine);
        foreach ($this->callbacks as $callback) {
            $callback->afterQueryExec($query, $result);
        }
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function executeQuery(string $query, bool $unbuffered = false): QueryResultInterface
    {
        return $this->execWithCallbacks($query, static fn(AbstractEngine $engine) =>
            $engine->executeQuery($query, $unbuffered));
    }

    /**
     * @inheritDoc
     */
    public function execute(string $query): bool
    {
        return $this->execWithCallbacks($query, static fn(AbstractEngine $engine) =>
            $engine->execute($query));
    }

    /**
     * @inheritDoc
     */
    public function columnValue(string $query, string|int $column = -1): mixed
    {
        return $this->execWithCallbacks($query, static fn(AbstractEngine $engine) =>
            $engine->columnValue($query, $column));
    }

    /**
     * @inheritDoc
     */
    public function executeMultiQuery(string $query): QueryResultInterface
    {
        return $this->execWithCallbacks($query, static fn(AbstractEngine $engine) =>
            $engine->executeMultiQuery($query));
    }
}
