<?php

namespace Lagdo\DbAdmin\Db\Database;

use Lagdo\DbAdmin\Db\AbstractFacade;
use Exception;

/**
 * Facade to database functions
 */
trait DatabaseTrait
{
    /**
     * The proxy
     *
     * @var DatabaseFacade
     */
    protected $databaseFacade = null;

    /**
     * @return AbstractFacade
     */
    abstract public function facade(): AbstractFacade;

    /**
     * Connect to a database server
     *
     * @param string $server    The selected server
     * @param string $database  The database name
     * @param string $schema    The database schema
     *
     * @return void
     */
    abstract public function connect(string $server, string $database = '', string $schema = '');

    /**
     * Set the breadcrumbs items
     *
     * @param array $breadcrumbs
     *
     * @return void
     */
    abstract protected function setBreadcrumbs(array $breadcrumbs);

    /**
     * Get the proxy
     *
     * @param array $options    The server config options
     *
     * @return DatabaseFacade
     */
    protected function database(array $options)
    {
        if (!$this->databaseFacade) {
            $this->databaseFacade = new DatabaseFacade($options);
            $this->databaseFacade->init($this->facade());
        }
        return $this->databaseFacade;
    }

    /**
     * Connect to a database server
     *
     * @param string $server    The selected server
     * @param string $database  The database name
     *
     * @return array
     */
    public function getDatabaseInfo(string $server, string $database)
    {
        $this->connect($server, $database);

        $package = $this->facade()->package;
        $this->setBreadcrumbs([$package->getServerName($server), $database]);

        return $this->database($package->getServerOptions($server))->getDatabaseInfo();
    }

    /**
     * Get the tables from a database server
     *
     * @param string $server    The selected server
     * @param string $database  The database name
     * @param string $schema    The database schema
     *
     * @return array
     */
    public function getTables(string $server, string $database, string $schema)
    {
        $this->connect($server, $database, $schema);

        $package = $this->facade()->package;
        $this->setBreadcrumbs([$package->getServerName($server), $database, $this->trans->lang('Tables')]);

        return $this->database($package->getServerOptions($server))->getTables();
    }

    /**
     * Get the views from a database server
     *
     * @param string $server    The selected server
     * @param string $database  The database name
     * @param string $schema    The database schema
     *
     * @return array
     */
    public function getViews(string $server, string $database, string $schema)
    {
        $this->connect($server, $database, $schema);

        $package = $this->facade()->package;
        $this->setBreadcrumbs([$package->getServerName($server), $database, $this->trans->lang('Views')]);

        return $this->database($package->getServerOptions($server))->getViews();
    }

    /**
     * Get the routines from a given database
     *
     * @param string $server    The selected server
     * @param string $database  The database name
     * @param string $schema    The database schema
     *
     * @return array
     */
    public function getRoutines(string $server, string $database, string $schema)
    {
        $this->connect($server, $database, $schema);

        $package = $this->facade()->package;
        $this->setBreadcrumbs([$package->getServerName($server), $database, $this->trans->lang('Routines')]);

        return $this->database($package->getServerOptions($server))->getRoutines();
    }

    /**
     * Get the sequences from a given database
     *
     * @param string $server    The selected server
     * @param string $database  The database name
     * @param string $schema    The database schema
     *
     * @return array
     */
    public function getSequences(string $server, string $database, string $schema)
    {
        $this->connect($server, $database, $schema);

        $package = $this->facade()->package;
        $this->setBreadcrumbs([$package->getServerName($server), $database, $this->trans->lang('Sequences')]);

        return $this->database($package->getServerOptions($server))->getSequences();
    }

    /**
     * Get the user types from a given database
     *
     * @param string $server    The selected server
     * @param string $database  The database name
     * @param string $schema    The database schema
     *
     * @return array
     */
    public function getUserTypes(string $server, string $database, string $schema)
    {
        $this->connect($server, $database, $schema);

        $package = $this->facade()->package;
        $this->setBreadcrumbs([$package->getServerName($server), $database, $this->trans->lang('User types')]);

        return $this->database($package->getServerOptions($server))->getUserTypes();
    }

    /**
     * Get the events from a given database
     *
     * @param string $server    The selected server
     * @param string $database  The database name
     * @param string $schema    The database schema
     *
     * @return array
     */
    public function getEvents(string $server, string $database, string $schema)
    {
        $this->connect($server, $database, $schema);

        $package = $this->facade()->package;
        $this->setBreadcrumbs([$package->getServerName($server), $database, $this->trans->lang('Events')]);

        return $this->database($package->getServerOptions($server))->getEvents();
    }
}
