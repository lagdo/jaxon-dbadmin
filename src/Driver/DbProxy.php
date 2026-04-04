<?php

namespace Lagdo\DbAdmin\Db\Driver;

use Jaxon\App\View\ViewRenderer;
use Jaxon\Di\Container;
use Lagdo\DbAdmin\Db\Driver\AbstractProxy;
use Lagdo\DbAdmin\Db\UiData\AppPage;
use Lagdo\DbAdmin\Db\Service\Breadcrumbs;
use Lagdo\DbAdmin\Support\Utils\Utils;

/**
 * Proxy to calls to the database functions
 */
class DbProxy extends AbstractProxy
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
     * @param ViewRenderer $viewRenderer
     */
    public function __construct(protected Container $di, protected Utils $utils,
        protected ViewRenderer $viewRenderer)
    {
        $this->setDriver();
        // Make the translator available into views
        $viewRenderer->share('trans', $utils->trans);
        $this->breadcrumbs = new Breadcrumbs();
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
        if (!$this->driver()) {
            // Save the selected server in the di container.
            $this->di->val('dbadmin_config_server', $server);
            // The DI is now able to return the corresponding driver.
            $driver = $this->di->get(AppDriver::class);
            $page = $this->di->get(AppPage::class);
            $this->setDriver($driver)->setPage($page)->setUtils($this->utils);
        }
        // Open the selected database
        $this->driver()->openConnection($database, $schema);
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
