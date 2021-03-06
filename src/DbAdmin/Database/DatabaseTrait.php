<?php

namespace Lagdo\DbAdmin\DbAdmin\Database;

use Lagdo\DbAdmin\DbAdmin\AbstractAdmin;
use Exception;

/**
 * Admin database functions
 */
trait DatabaseTrait
{
    /**
     * The proxy
     *
     * @var DatabaseAdmin
     */
    protected $databaseAdmin = null;

    /**
     * @return AbstractAdmin
     */
    abstract public function admin(): AbstractAdmin;

    /**
     * Connect to a database server
     *
     * @param string $server    The selected server
     * @param string $database  The database name
     * @param string $schema    The database schema
     *
     * @return array
     */
    abstract public function connect(string $server, string $database = '', string $schema = ''): array;

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
     * @return DatabaseAdmin
     */
    protected function database(array $options)
    {
        if (!$this->databaseAdmin) {
            $this->databaseAdmin = new DatabaseAdmin($options);
            $this->databaseAdmin->init($this->admin());
        }
        return $this->databaseAdmin;
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

        $options = $this->package->getServerOptions($server);
        $this->setBreadcrumbs([$options['name'], $database]);

        return $this->database($options)->getDatabaseInfo();
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

        $options = $this->package->getServerOptions($server);
        $this->setBreadcrumbs([$options['name'], $database, $this->trans->lang('Tables')]);

        return $this->database($options)->getTables();
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

        $options = $this->package->getServerOptions($server);
        $this->setBreadcrumbs([$options['name'], $database, $this->trans->lang('Views')]);

        return $this->database($options)->getViews();
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

        $options = $this->package->getServerOptions($server);
        $this->setBreadcrumbs([$options['name'], $database, $this->trans->lang('Routines')]);

        return $this->database($options)->getRoutines();
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

        $options = $this->package->getServerOptions($server);
        $this->setBreadcrumbs([$options['name'], $database, $this->trans->lang('Sequences')]);

        return $this->database($options)->getSequences();
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

        $options = $this->package->getServerOptions($server);
        $this->setBreadcrumbs([$options['name'], $database, $this->trans->lang('User types')]);

        return $this->database($options)->getUserTypes();
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

        $options = $this->package->getServerOptions($server);
        $this->setBreadcrumbs([$options['name'], $database, $this->trans->lang('Events')]);

        return $this->database($options)->getEvents();
    }
}
