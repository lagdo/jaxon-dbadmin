<?php

namespace Lagdo\DbAdmin\Db\Traits;

use Lagdo\DbAdmin\Db\Facades\DatabaseFacade;

/**
 * Facade to database functions
 */
trait DatabaseTrait
{
    use AbstractTrait;

    /**
     * Get the facade
     *
     * @return DatabaseFacade
     */
    protected function databaseFacade()
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
        return $this->databaseFacade()->getDatabaseInfo();
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
        return $this->databaseFacade()->getTables();
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
        return $this->databaseFacade()->getViews();
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
        return $this->databaseFacade()->getRoutines();
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
        return $this->databaseFacade()->getSequences();
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
        return $this->databaseFacade()->getUserTypes();
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
        return $this->databaseFacade()->getEvents();
    }
}
