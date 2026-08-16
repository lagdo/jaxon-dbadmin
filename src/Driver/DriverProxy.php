<?php

namespace Lagdo\DbAdmin\Support\Driver;

use Jaxon\Di\Container;
use Lagdo\DbAdmin\Driver\Driver;
use Lagdo\DbAdmin\Driver\Sql\Dto\ColumnDto;
use Lagdo\DbAdmin\Support\Service\Breadcrumbs;

/**
 * Proxy to calls to the database functions, for the UI components.
 */
class DriverProxy extends AbstractDriverProxy
{
    use Proxy\DatabaseTrait;
    use Proxy\TableTrait;
    use Proxy\QueryTrait;

    /**
     * @var array
     */
    protected array $connected = ['', '', ''];

    /**
     * @param Container $di
     * @param DriverHelper $helper
     * @param Breadcrumbs $breadcrumbs
     */
    public function __construct(protected Container $di,
        DriverHelper $helper, protected Breadcrumbs $breadcrumbs)
    {
        $this->currentDb = new CurrentDbDto();
        $this->driverHelper = $helper;
    }

    /**
     * @return string
     */
    public function getServerName(): string
    {
        return $this->currentDb()->server;
    }

    /**
     * Get the breadcrumbs object
     *
     * @param bool $withDb
     *
     * @return Breadcrumbs
     */
    public function breadcrumbs(bool $withDb = false): Breadcrumbs
    {
        if ($withDb) {
            $this->breadcrumbs->clear();
            if ($this->currentDb()->name) {
                $this->breadcrumbs->item("<i><b>{$this->currentDb()->name}</b></i>");
            }
        }
        return $this->breadcrumbs;
    }

    /**
     * @return Container
     */
    public function di(): Container
    {
        return $this->di;
    }

    /**
     * Set the current database
     *
     * @param string $server    The selected server
     * @param string $database  The database name
     * @param string $schema    The database schema
     *
     * @return void
     */
    public function selectDatabase(string $server, string $database = '', string $schema = '')
    {
        $this->currentDb()->setServer($server);
        $this->currentDb()->setName($database);
        $this->currentDb()->setSchema($schema);

        // Save the selected server in the di container.
        $this->di->val('dbadmin_config_server', $server);
        // The DI is now able to return the corresponding driver.
        $this->helper()->setDriver($this->di->get(Driver::class));
    }

    /**
     * Set the current database name
     *
     * @param string $database  The database name
     *
     * @return void
     */
    public function setCurrentDbName(string $database)
    {
        $this->currentDb()->setName($database);
    }

    /**
     * Connect to a database server
     *
     * @param string $server    The selected server
     * @param string $database  The database name
     * @param string $schema    The database schema
     *
     * @return void
     */
    private function connect(string $server, string $database = '', string $schema = '')
    {
        $dbChanged = $this->connected[0] !== $server ||
            $this->connected[1] !== $database ||
            $this->connected[2] !== $schema;
        if ($dbChanged) {
            // Open the selected database
            $this->engine()->openMainConnection($database, $schema);
            $this->connected = [$server, $database, $schema];
        }
    }

    /**
     * @return void
     */
    public function connectToServer()
    {
        $this->connect($this->currentDb()->server);
    }

    /**
     * @return void
     */
    public function connectToDatabase()
    {
        $this->connect($this->currentDb()->server, $this->currentDb()->name);
    }

    /**
     * @return void
     */
    public function connectToSchema()
    {
        $this->connect($this->currentDb()->server,
            $this->currentDb()->name, $this->currentDb()->schema);
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    public function typeIsAutoIncrementable(string $type): bool
    {
        return $this->engine()->typeIsAutoIncrementable($type);
    }

    /**
     * @param array $where
     * @param array<ColumnDto> $columns
     *
     * @return string
     */
    public function getSelectWhereClause(array $where, array $columns = []): string
    {
        return $this->engine()->where($where, $columns);
    }
}
