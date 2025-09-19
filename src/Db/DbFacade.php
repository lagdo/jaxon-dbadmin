<?php

namespace Lagdo\DbAdmin\Db;

use Jaxon\App\View\ViewRenderer;
use Jaxon\Di\Container;
use Lagdo\DbAdmin\Admin\Admin;
use Lagdo\DbAdmin\Db\Facades\AbstractFacade;
use Lagdo\DbAdmin\Driver\Utils\ConfigInterface;
use Lagdo\DbAdmin\Driver\Utils\Utils;

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
     * @var Breadcrumbs
     */
    protected $breadcrumbs;

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
     * @param ConfigInterface $config
     * @param ViewRenderer $viewRenderer
     */
    public function __construct(protected Container $di, protected Utils $utils,
        protected ConfigInterface $config, protected ViewRenderer $viewRenderer)
    {
        // Make the translator available into views
        $viewRenderer->share('trans', $utils->trans);
        $this->breadcrumbs = new Breadcrumbs();
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
        if (!$this->driver) {
            // Save the selected server in the di container.
            $this->di->val('dbadmin_config_server', $server);
            // The DI is now able to return the corresponding driver.
            $this->driver = $this->di->get(CallbackDriver::class);
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
        return $this->config->options();
    }

    /**
     * @return array
     */
    public function getDatabaseOptions(): array
    {
        $options = $this->getServerOptions();
        if ($this->dbName !== '') {
            $options['database'] = $this->dbName;
        }
        if ($this->dbSchema !== '') {
            $options['schema'] = $this->dbSchema;
        }
        return $options;
    }
}
