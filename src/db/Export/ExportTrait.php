<?php

namespace Lagdo\DbAdmin\Db\Export;

use Lagdo\DbAdmin\Db\AbstractFacade;
use Exception;

/**
 * Facade to export functions
 */
trait ExportTrait
{
    /**
     * The proxy
     *
     * @var ExportFacade
     */
    protected $exportFacade = null;

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
     * @return ExportFacade
     */
    protected function export(): ExportFacade
    {
        if (!$this->exportFacade) {
            $this->exportFacade = new ExportFacade();
            $this->exportFacade->init($this->facade());
        }
        return $this->exportFacade;
    }

    /**
     * Get data for export
     *
     * @param string $server        The selected server
     * @param string $database      The database name
     *
     * @return array
     */
    public function getExportOptions(string $server, string $database = ''): array
    {
        $this->connect($server, $database);

        $package = $this->facade()->package;
        $breadcrumbs = [$package->getServerName($server)];
        if (($database)) {
            $breadcrumbs[] = $database;
        }
        $breadcrumbs[] = $this->trans->lang('Export');
        $this->setBreadcrumbs($breadcrumbs);

        return $this->export()->getExportOptions($database);
    }

    /**
     * Export databases
     * The databases and tables parameters are array where the keys are names and the values
     * are boolean which indicate whether the corresponding data should be exported too.
     *
     * @param string $server        The selected server
     * @param array  $databases     The databases to dump
     * @param array  $tables        The tables to dump
     * @param array  $dumpOptions   The export options
     *
     * @return array|string
     */
    public function exportDatabases(string $server, array $databases, array $tables, array $dumpOptions)
    {
        $this->connect($server);
        return $this->export()->exportDatabases($databases, $tables, $dumpOptions);
    }
}
