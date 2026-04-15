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
    use Proxy\ServerTrait;
    use Proxy\UserTrait;
    use Proxy\DatabaseTrait;
    use Proxy\TableTrait;
    use Proxy\SelectTrait;
    use Proxy\QueryTrait;
    use Proxy\ViewTrait;
    use Proxy\CommandTrait;
    use Proxy\ExportTrait;
    use Proxy\ImportTrait;

    /**
     * @var bool
     */
    protected bool $connected = false;

    /**
     * @var string
     */
    protected string $dbServer;

    /**
     * @var string
     */
    protected string $dbName;

    /**
     * @var string
     */
    protected string $dbSchema;

    /**
     * @param Container $di
     * @param DriverHelper $helper
     * @param Breadcrumbs $breadcrumbs
     */
    public function __construct(protected Container $di,
        DriverHelper $helper, protected Breadcrumbs $breadcrumbs)
    {
        parent::__construct($helper);
    }

    /**
     * @return string
     */
    public function getServerName(): string
    {
        return $this->dbServer;
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
            if ($this->dbName) {
                $this->breadcrumbs->item("<i><b>$this->dbName</b></i>");
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
        $this->dbServer = $server;
        $this->dbName = $database;
        $this->dbSchema = $schema;
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
        $this->dbName = $database;
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
        // Prevent multiple calls.
        if (!$this->connected) {
            // Save the selected server in the di container.
            $this->di->val('dbadmin_config_server', $server);
            // The DI is now able to return the corresponding driver.
            $this->helper()->setDriver($this->di->get(Driver::class));
            $this->connected = true;
        }

        // Open the selected database
        $this->engine()->openMainConnection($database, $schema);
    }

    /**
     * @return void
     */
    public function connectToServer()
    {
        $this->connect($this->dbServer);
    }

    /**
     * @return void
     */
    public function connectToDatabase()
    {
        $this->connect($this->dbServer, $this->dbName);
    }

    /**
     * @return void
     */
    public function connectToSchema()
    {
        $this->connect($this->dbServer, $this->dbName, $this->dbSchema);
    }

    /**
     * @param array $options
     *
     * @return array
     */
    public function getDatabaseOptions(array $options): array
    {
        if ($this->dbName !== '') {
            $options['database'] = $this->dbName;
        }
        if ($this->dbSchema !== '') {
            $options['schema'] = $this->dbSchema;
        }
        return $options;
    }
}
