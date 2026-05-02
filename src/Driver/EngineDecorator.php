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
use Exception;

use function strlen;
use function substr;

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
    public function executeQuery(string $query, bool $unbuffered = false): QueryResultInterface
    {
        $result = $this->engine->executeQuery($query, $unbuffered);
        // Call the query callbacks.
        $this->callCallbacks($query);
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function execute(string $query): bool
    {
        $result = $this->engine->execute($query);
        // Call the query callbacks.
        $this->callCallbacks($query);
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function columnValue(string $query, string|int $column = -1): mixed
    {
        $result = $this->engine->columnValue($query, $column);
        // Call the query callbacks.
        $this->callCallbacks($query);
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function executeMultiQuery(string $query): QueryResultInterface
    {
        $result = $this->engine->executeMultiQuery($query);
        // Call the query callbacks.
        $this->callCallbacks($query);
        return $result;
    }

    /**
     * Query printed after execution in the message
     *
     * @param string $query Executed query
     *
     * @return string
     */
    // private function queryToLog(string $query/*, string $time*/): string
    // {
    //     if (strlen($query) > 1e6) {
    //         // [\x80-\xFF] - valid UTF-8, \n - can end by one-line comment
    //         $query = preg_replace('~[\x80-\xFF]+$~', '', substr($query, 0, 1e6)) . "\n…";
    //     }
    //     return $query;
    // }

    /**
     * Execute query
     *
     * @param string $query
     * @param bool $execute
     * @param bool $failed
     *
     * @return bool
     * @throws Exception
     */
    // public function executeQuery(string $query, bool $execute = true,
    //     bool $failed = false/*, string $time = ''*/): bool
    // {
    //     if ($execute) {
    //         // $start = microtime(true);
    //         $failed = !$this->execute($query);
    //         // $time = $this->trans->formatTime($start);
    //     }
    //     if ($failed) {
    //         $sql = '';
    //         if ($query) {
    //             $sql = $this->queryToLog($query/*, $time*/);
    //         }
    //         throw new Exception($this->_engine()->error() . $sql);
    //     }
    //     return true;
    // }
}
