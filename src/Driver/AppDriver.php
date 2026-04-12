<?php

namespace Lagdo\DbAdmin\Db\Driver;

use Jaxon\Di\Container;
use Lagdo\DbAdmin\Support\AbstractDriver;
use Lagdo\DbAdmin\Support\AbstractGrammar;
use Lagdo\DbAdmin\Support\Db\Admin\Config\DriverConfig;
use Lagdo\DbAdmin\Support\Db\Engine\Connection\StatementInterface;
use Lagdo\DbAdmin\Support\Db\Engine\Driver\AbstractDatabase;
use Lagdo\DbAdmin\Support\Db\Engine\Driver\AbstractQuery;
use Lagdo\DbAdmin\Support\Db\Engine\Driver\AbstractServer;
use Lagdo\DbAdmin\Support\Db\Engine\Driver\AbstractTable;
use Lagdo\DbAdmin\Support\DriverInterface;
use Lagdo\DbAdmin\Support\Utils\Utils;
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
    }

    /**
     * @return DriverConfig
     */
    protected function config(): DriverConfig
    {
        return $this->driver->config();
    }

    /**
     * @return AbstractDriver
     */
    protected function _driver(): AbstractDriver
    {
        return $this->driver;
    }

    /**
     * @return AbstractGrammar
     */
    protected function _grammar(): AbstractGrammar
    {
        return $this->driver->_grammar();
    }

    /**
     * @return Utils
     */
    protected function _utils(): Utils
    {
        return $this->driver->_utils();
    }

    /**
     * @return AbstractServer
     */
    protected function _server(): AbstractServer
    {
        return $this->driver->_server();
    }

    /**
     * @return AbstractDatabase
     */
    protected function _database(): AbstractDatabase
    {
        return $this->driver->_database();
    }

    /**
     * @return AbstractTable
     */
    protected function _table(): AbstractTable
    {
        return $this->driver->_table();
    }

    /**
     * @return AbstractQuery
     */
    protected function _query(): AbstractQuery
    {
        return $this->driver->_query();
    }

    /**
     * @inheritDoc
     */
    public function name(): string
    {
        return $this->driver->name();
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
