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
     *
     * @return void
     */
    private function callBeforeCallbacks(string $query): void
    {
        foreach ($this->callbacks as $callback) {
            $callback->beforeQueryExec($query);
        }
    }

    /**
     * @param string $query
     * @param QueryResultInterface|bool $result
     *
     * @return void
     */
    private function callAfterCallbacks(string $query, QueryResultInterface|bool $result): void
    {
        foreach ($this->callbacks as $callback) {
            $callback->afterQueryExec($query, $result);
        }
    }

    /**
     * @inheritDoc
     */
    public function executeQuery(string $query, bool $unbuffered = false): QueryResultInterface
    {
        $this->callBeforeCallbacks($query);
        $result = $this->engine->executeQuery($query, $unbuffered);
        $this->callAfterCallbacks($query, $result);
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function execute(string $query): bool
    {
        $this->callBeforeCallbacks($query);
        $result = $this->engine->execute($query);
        $this->callAfterCallbacks($query, $result);
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function columnValue(string $query, string|int $column = -1): mixed
    {
        $this->callBeforeCallbacks($query);
        $result = $this->engine->columnValue($query, $column);
        $this->callAfterCallbacks($query, $result !== null);
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function executeMultiQuery(string $query): QueryResultInterface
    {
        $this->callBeforeCallbacks($query);
        $result = $this->engine->executeMultiQuery($query);
        $this->callAfterCallbacks($query, $result);
        return $result;
    }
}
