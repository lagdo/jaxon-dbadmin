<?php

namespace Lagdo\DbAdmin\Support\Service\Audit;

use Lagdo\DbAdmin\Driver\EngineInterface;
use Lagdo\DbAdmin\Driver\Sql\Connection\AbstractConnection;
use Lagdo\DbAdmin\Driver\Sql\Connection\StatementInterface;
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
     * @param EngineInterface $engine
     * @param array $database
     */
    public function __construct(private EngineInterface $engine, array $database)
    {
        $connection = $engine->createConnection($database);
        if ($connection->open($database['name'], $database['schema'] ?? '')) {
            $this->connection = $connection;
        }
    }

    /**
     * @return bool
     */
    public function pgsql(): bool
    {
        return $this->engine->pgsql();
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
