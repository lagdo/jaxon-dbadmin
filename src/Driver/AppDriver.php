<?php

namespace Lagdo\DbAdmin\Db\Driver;

use Jaxon\Di\Container;
use Lagdo\DbAdmin\Driver\AbstractDriver;
use Lagdo\DbAdmin\Driver\Db\AbstractConnection;
use Lagdo\DbAdmin\Driver\Db\StatementInterface;
use Lagdo\DbAdmin\Driver\DriverInterface;
use Lagdo\DbAdmin\Driver\Driver\DatabaseInterface;
use Lagdo\DbAdmin\Driver\Driver\GrammarInterface;
use Lagdo\DbAdmin\Driver\Driver\QueryInterface;
use Lagdo\DbAdmin\Driver\Driver\ServerInterface;
use Lagdo\DbAdmin\Driver\Driver\TableInterface;
use Closure;

/**
 * Add callbacks to the driver features.
 */
class AppDriver extends AbstractDriver
{
    /**
     * @var array
     */
    private array $callbacks = [];

    /**
     * @param AbstractDriver $driver
     */
    public function __construct(protected AbstractDriver $driver)
    {
        // "Clone" the driver instance.
        $this->utils = $driver->utils;
        $this->config = $driver->config;
        $this->mainConnection = $driver->mainConnection;
        $this->connection = $driver->connection;
    }

    /**
     * @var ServerInterface
     */
    protected function _server(): ServerInterface
    {
        return $this->driver->_server();
    }

    /**
     * @var DatabaseInterface
     */
    protected function _database(): DatabaseInterface
    {
        return $this->driver->_database();
    }

    /**
     * @var TableInterface
     */
    protected function _table(): TableInterface
    {
        return $this->driver->_table();
    }

    /**
     * @var GrammarInterface
     */
    protected function _grammar(): GrammarInterface
    {
        return $this->driver->_grammar();
    }

    /**
     * @var QueryInterface
     */
    protected function _query(): QueryInterface
    {
        return $this->driver->_query();
    }

    /**
     * @inheritDoc
     */
    public function name()
    {
        return $this->driver->name();
    }

    /**
     * @return void
     */
    protected function beforeConnection(): void
    {
        $this->driver->beforeConnection();
    }

    /**
     * @return void
     */
    protected function configConnection(): void
    {
        $this->driver->configConnection();
    }

    /**
     * @return void
     */
    protected function connectionOpened(): void
    {
        $this->driver->connectionOpened();
    }

    /**
     * @inheritDoc
     */
    public function createConnection(array $options): AbstractConnection|null
    {
        return $this->driver->createConnection($options);
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
    public function addQueryCallback(Closure $callback): void
    {
        $this->callbacks[] = $callback;
    }

    /**
     * @inheritDoc
     */
    public function multiQuery(string $query): bool
    {
        $result = $this->driver->multiQuery($query);
        // Call the query callbacks.
        $this->callCallbacks($query);
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function result(string $query, int $field = -1): mixed
    {
        $result = $this->driver->result($query, $field);
        // Call the query callbacks.
        $this->callCallbacks($query);
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function execute(string $query): StatementInterface|bool
    {
        $result = $this->driver->execute($query);
        // Call the query callbacks.
        $this->callCallbacks($query);
        return $result;
    }

    /**
     * @param Container $di
     * @param array $options
     *
     * @return DriverInterface|null
     */
    public static function createDriver(Container $di, array $options): DriverInterface|null
    {
        $drivers = AbstractDriver::drivers();
        $driver = $options['driver'];
        $closure = $drivers[$driver] ?? null;
        return !$closure ? null : $closure($di, $options);
    }
}
