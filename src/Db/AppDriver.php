<?php

namespace Lagdo\DbAdmin\Db;

use Lagdo\DbAdmin\Driver\Driver;
use Closure;

/**
 * Add callbacks to the driver features.
 */
class AppDriver extends Driver
{
    /**
     * @var array
     */
    private array $callbacks = [];

    /**
     * @param Driver $driver
     */
    public function __construct(private Driver $driver)
    {
        // "Clone" the driver instance.
        $this->utils = $driver->utils;
        $this->config = $driver->config;
        $this->mainConnection = $driver->mainConnection;
        $this->connection = $driver->connection;

        $this->server = $driver->server;
        $this->database = $driver->database;
        $this->table = $driver->table;
        $this->query = $driver->query;
        $this->grammar = $driver->grammar;
    }

    /**
     * @inheritDoc
     */
    public function name()
    {
        return $this->driver->name();
    }

    /**
     * @inheritDoc
     */
    public function createConnection(array $options)
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
    public function multiQuery(string $query)
    {
        $result = $this->driver->multiQuery($query);
        // Call the query callbacks.
        $this->callCallbacks($query);
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function result(string $query, int $field = -1)
    {
        $result = $this->driver->result($query, $field);
        // Call the query callbacks.
        $this->callCallbacks($query);
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function execute(string $query)
    {
        $result = $this->driver->execute($query);
        // Call the query callbacks.
        $this->callCallbacks($query);
        return $result;
    }
}
