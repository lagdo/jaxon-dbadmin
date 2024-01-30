<?php

namespace Lagdo\DbAdmin\Db;

use Lagdo\DbAdmin\App\Package;
use Lagdo\DbAdmin\Driver\DriverInterface;
use Lagdo\DbAdmin\Driver\UtilInterface;
use Lagdo\DbAdmin\Translator;
use Jaxon\Di\Container;

use function array_unshift;
use function call_user_func_array;
use function func_get_args;

/**
 * Facade to calls to the database functions
 */
class DbFacade extends AbstractFacade
{
    use Server\ServerTrait;
    use User\UserTrait;
    use Database\DatabaseTrait;
    use Table\TableTrait;
    use Select\SelectTrait;
    use Query\QueryTrait;
    use View\ViewTrait;
    use Command\CommandTrait;
    use Export\ExportTrait;
    use Import\ImportTrait;

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
     * @param Package $package
     * @param Translator $trans
     */
    public function __construct(Container $di, Package $package, Translator $trans)
    {
        $this->di = $di;
        $this->package = $package;
        $this->trans = $trans;
        // Make the translator available into views
        $this->package->view()->share('trans', $this->trans);
    }

    /**
     * Get a translated string
     * The first parameter is mandatory. Optional parameters can follow.
     *
     * @param string
     *
     * @return string
     */
    public function lang($idf): string
    {
        return call_user_func_array([$this->trans, "lang"], func_get_args());
    }

    /**
     * Get the breadcrumbs items
     *
     * @return array
     */
    public function getBreadcrumbs(): array
    {
        return $this->breadcrumbs;
    }

    /**
     * Set the breadcrumbs items
     *
     * @param bool $showDatabase
     * @param array $breadcrumbs
     *
     * @return void
     */
    protected function setBreadcrumbs(bool $showDatabase = false, array $breadcrumbs = [])
    {
        $this->breadcrumbs = $breadcrumbs;
        array_unshift($this->breadcrumbs, $this->package->getServerName($this->dbServer));
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
    public function setCurrentDb(string $server, string $database = '', string $schema = '')
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
    public function connect(string $server, string $database = '', string $schema = '')
    {
        // Prevent multiple calls.
        if (!$this->driver) {
            // Save the selected server options in the di container.
            $this->di->val('dbadmin_config_driver', $this->package->getServerDriver($server));
            $this->di->val('dbadmin_config_options', $this->package->getServerOptions($server));
            $this->driver = $this->di->get(DriverInterface::class);
            $this->util = $this->di->get(UtilInterface::class);
        }
        // Connect to the selected server
        $this->driver->connect($database, $schema);
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
