<?php

namespace Lagdo\DbAdmin\Support\Driver;

use Jaxon\Di\Container;
use Lagdo\DbAdmin\Support\Service\Breadcrumbs;
use Lagdo\DbAdmin\Driver\Driver;

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
        return $this->db()->server;
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
            if ($this->db()->name) {
                $this->breadcrumbs->item("<i><b>{$this->db()->name}</b></i>");
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
        $this->db()->server = $server;
        $this->db()->name = $database;
        $this->db()->schema = $schema;

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
        $this->db()->name = $database;
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
        $this->connect($this->db()->server);
    }

    /**
     * @return void
     */
    public function connectToDatabase()
    {
        $this->connect($this->db()->server, $this->db()->name);
    }

    /**
     * @return void
     */
    public function connectToSchema()
    {
        $this->connect($this->db()->server, $this->db()->name, $this->db()->schema);
    }

    /**
     * @param array $options
     *
     * @return array
     */
    public function getDatabaseOptions(array $options): array
    {
        if ($this->db()->name !== '') {
            $options['database'] = $this->db()->name;
        }
        if ($this->db()->schema !== '') {
            $options['schema'] = $this->db()->schema;
        }

        return $options;
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
}
