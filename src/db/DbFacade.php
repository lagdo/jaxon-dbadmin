<?php

namespace Lagdo\DbAdmin\Db;

use Jaxon\Di\Container;
use Lagdo\DbAdmin\Admin\Admin;
use Lagdo\DbAdmin\Db\Facades\AbstractFacade;
use Lagdo\DbAdmin\Driver\Utils\Utils;
use Lagdo\DbAdmin\Driver\DriverInterface;
use Lagdo\DbAdmin\Package;

use function array_merge;

/**
 * Facade to calls to the database functions
 */
class DbFacade extends AbstractFacade
{
    use Traits\ServerTrait;
    use Traits\UserTrait;
    use Traits\DatabaseTrait;
    use Traits\TableTrait;
    use Traits\SelectTrait;
    use Traits\QueryTrait;
    use Traits\ViewTrait;
    use Traits\CommandTrait;
    use Traits\ExportTrait;
    use Traits\ImportTrait;

    /**
     * The breadcrumbs items
     *
     * @var array
     */
    protected $breadcrumbs = [];

    /**
     * @var Container
     */
    protected $di;

    /**
     * @var string
     */
    protected $dbServer;

    /**
     * @var string
     */
    protected $dbName;

    /**
     * @var string
     */
    protected $dbSchema;

    /**
     * The constructor
     *
     * @param Container $di
     * @param Utils $utils
     * @param Package $package
     */
    public function __construct(Container $di, Utils $utils, Package $package)
    {
        $this->di = $di;
        $this->utils = $utils;
        $this->package = $package;
        // Make the translator available into views
        $this->package->view()->share('trans', $this->utils->trans);
    }

    /**
     * Get the breadcrumbs items
     *
     * @return array
     */
    public function getBreadcrumbs(): array
    {
        return array_merge([$this->package->getServerName($this->dbServer)], $this->breadcrumbs);
    }

    /**
     * Clear the breadcrumbs
     *
     * @return self
     */
    protected function bccl(): self
    {
        $this->breadcrumbs = [];
        return $this;
    }

    /**
     * Add the selected DB name to the breadcrumbs
     *
     * @return self
     */
    protected function bcdb(): self
    {
        $this->breadcrumbs = !$this->dbName ? [] : [$this->dbName];
        return $this;
    }

    /**
     * Add an item to the breadcrumbs
     *
     * @param string $label
     *
     * @return self
     */
    protected function breadcrumb(string $label): self
    {
        $this->breadcrumbs[] = $label;
        return $this;
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
     * Get the current server
     *
     * @return string
     */
    public function getCurrentServer(): string
    {
        return $this->dbServer;
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
    public function connect(string $server, string $database = '', string $schema = '')
    {
        // Prevent multiple calls.
        if (!$this->driver) {
            // Save the selected server options in the di container.
            $this->di->val('dbadmin_config_driver', $this->package->getServerDriver($server));
            $this->di->val('dbadmin_config_options', $this->package->getServerOptions($server));
            $this->driver = $this->di->get(DriverInterface::class);
            $this->admin = $this->di->get(Admin::class);
        }
        // Open the selected database
        $this->driver->open($database, $schema);
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
     * @return array
     */
    public function getServerOptions(): array
    {
        return $this->package->getServerOptions($this->dbServer);
    }

    /**
     * Get the remembered queries
     *
     * @return array
     */
    public function queries(): array
    {
        return $this->driver->queries();
    }
}
