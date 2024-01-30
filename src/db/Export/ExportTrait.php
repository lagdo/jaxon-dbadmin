<?php

namespace Lagdo\DbAdmin\Db\Export;

use Jaxon\Di\Container;

/**
 * Facade to export functions
 */
trait ExportTrait
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
    abstract public function connectToServer();

    /**
     * Connect to a database server
     *
     * @return void
     */
    abstract public function connectToDatabase();

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
     * @return ExportFacade
     */
    protected function export(): ExportFacade
    {
        return $this->di()->g(ExportFacade::class);
    }

    /**
     * Get data for export
     *
     * @param string $database      The database name
     *
     * @return array
     */
    public function getExportOptions(string $database = ''): array
    {
        $this->connectToDatabase();

        $this->setBreadcrumbs(!!$this->dbName, [$this->trans->lang('Export')]);

        return $this->export()->getExportOptions($database);
    }

    /**
     * Export databases
     * The databases and tables parameters are array where the keys are names and the values
     * are boolean which indicate whether the corresponding data should be exported too.
     *
     * @param array  $databases     The databases to dump
     * @param array  $tables        The tables to dump
     * @param array  $dumpOptions   The export options
     *
     * @return array|string
     */
    public function exportDatabases(array $databases, array $tables, array $dumpOptions)
    {
        $this->connectToServer();
        return $this->export()->exportDatabases($databases, $tables, $dumpOptions);
    }
}
