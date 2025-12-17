<?php

namespace Lagdo\DbAdmin\Db\Service\Audit;

use Lagdo\DbAdmin\Driver\Db\AbstractConnection;
use Lagdo\DbAdmin\Driver\Db\StatementInterface;
use Lagdo\DbAdmin\Driver\DriverInterface;
use Lagdo\Facades\Logger;

/**
 * Connection to the audit database
 */
class ConnectionProxy
{
    /**
     * @var AbstractConnection|null
     */
    private AbstractConnection|null $connection = null;

    /**
     * The constructor
     *
     * @param DriverInterface $driver
     * @param array $database
     */
    public function __construct(private DriverInterface $driver, array $database)
    {
        $this->connect($driver, $database);
    }

    /**
     * @param DriverInterface $driver
     * @param array $database
     *
     * @return void
     */
    private function connect(DriverInterface $driver, array $database): void
    {
        $connection = $driver->createConnection($database);
        if ($connection->open($database['name'], $database['schema'] ?? '')) {
            $this->connection = $connection;
        }
    }

    /**
     * @return string
     */
    public function jush(): string
    {
        return $this->driver->jush();
    }

    /**
     * @return bool
     */
    public function connected(): bool
    {
        return $this->connection !== null;
    }

    /**
     * @param string $message
     *
     * @return void
     */
    public function logWarning(string $message): void
    {
        Logger::warning($message, [
            'message' => $this->connection?->error() ?? 'Unable to connect to the audit database.',
        ]);
    }

    /**
     * @param string $query
     * @param array|null $values
     *
     * @return bool|StatementInterface
     */
    public function executeQuery(string $query, array|null $values = null): bool|StatementInterface
    {
        if ($this->connection === null) {
            return false;
        }

        if ($values === null) {
            return $this->connection->query($query);
        }

        $st = $this->connection->prepareStatement($query);
        return $this->connection->executeStatement($st, $values) ?? false;
    }
}
