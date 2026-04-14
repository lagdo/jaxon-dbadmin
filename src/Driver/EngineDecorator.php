<?php

namespace Lagdo\DbAdmin\Db\Driver;

use Lagdo\DbAdmin\Driver\AbstractEngine;
use Lagdo\DbAdmin\Driver\AbstractStatement;
use Lagdo\DbAdmin\Driver\Sql\Config\DriverConfig;
use Lagdo\DbAdmin\Driver\Sql\Connection\StatementInterface;
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
     * @var array
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
     * @inheritDoc
     */
    public function addQueryCallback(Closure $callback): void
    {
        $this->callbacks[] = $callback;
    }

    /**
     * @param string $query
     *
     * @return void
     */
    private function callCallbacks(string $query): void
    {
        foreach ($this->callbacks as $callback) {
            $callback($query);
        }
    }

    /**
     * @inheritDoc
     */
    public function multiQuery(string $query): bool
    {
        $result = $this->engine->multiQuery($query);
        // Call the query callbacks.
        $this->callCallbacks($query);
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function result(string $query, int $field = -1): mixed
    {
        $result = $this->engine->result($query, $field);
        // Call the query callbacks.
        $this->callCallbacks($query);
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function execute(string $query): StatementInterface|bool
    {
        $result = $this->engine->execute($query);
        // Call the query callbacks.
        $this->callCallbacks($query);
        return $result;
    }
}
