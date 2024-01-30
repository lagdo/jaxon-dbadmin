<?php

namespace Lagdo\DbAdmin\Db\Database;

use Jaxon\Di\Container;

/**
 * Facade to database functions
 */
trait DatabaseTrait
{
    /**
     * @return Container
     */
    abstract public function di(): Container;

    /**
     * Connect to a database server
     *
     * @return void
     */
    abstract public function connectToDatabase();

    /**
     * Connect to a database server
     *
     * @return void
     */
    abstract public function connectToSchema();

    /**
     * Set the breadcrumbs items
     *
     * @param bool $showDatabase
     * @param array $breadcrumbs
     *
     * @return void
     */
    abstract protected function setBreadcrumbs(bool $showDatabase = false, array $breadcrumbs = []);

    /**
     * Get the proxy
     *
     * @return DatabaseFacade
     */
    protected function database()
    {
        return $this->di()->g(DatabaseFacade::class);
    }

    /**
     * Connect to a database server
     *
     * @return array
     */
    public function getDatabaseInfo()
    {
        $this->connectToDatabase();

        $this->setBreadcrumbs(true);

        return $this->database()->getDatabaseInfo();
    }

    /**
     * Get the tables from a database server
     *
     * @return array
     */
    public function getTables()
    {
        $this->connectToSchema();

        $this->setBreadcrumbs(true, [$this->trans->lang('Tables')]);

        return $this->database()->getTables();
    }

    /**
     * Get the views from a database server
     *
     * @return array
     */
    public function getViews()
    {
        $this->connectToSchema();

        $this->setBreadcrumbs(true, [$this->trans->lang('Views')]);

        return $this->database()->getViews();
    }

    /**
     * Get the routines from a given database
     *
     * @return array
     */
    public function getRoutines()
    {
        $this->connectToSchema();

        $this->setBreadcrumbs(true, [$this->trans->lang('Routines')]);

        return $this->database()->getRoutines();
    }

    /**
     * Get the sequences from a given database
     *
     * @return array
     */
    public function getSequences()
    {
        $this->connectToSchema();

        $this->setBreadcrumbs(true, [$this->trans->lang('Sequences')]);

        return $this->database()->getSequences();
    }

    /**
     * Get the user types from a given database
     *
     * @return array
     */
    public function getUserTypes()
    {
        $this->connectToSchema();

        $this->setBreadcrumbs(true, [$this->trans->lang('User types')]);

        return $this->database()->getUserTypes();
    }

    /**
     * Get the events from a given database
     *
     * @return array
     */
    public function getEvents()
    {
        $this->connectToSchema();

        $this->setBreadcrumbs(true, [$this->trans->lang('Events')]);

        return $this->database()->getEvents();
    }
}
