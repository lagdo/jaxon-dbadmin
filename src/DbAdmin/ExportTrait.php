<?php

namespace Lagdo\DbAdmin\DbAdmin;

use Exception;

/**
 * Admin export functions
 */
trait ExportTrait
{
    /**
     * The proxy
     *
     * @var ExportAdmin
     */
    protected $exportAdmin = null;

    /**
     * @return AbstractAdmin
     */
    abstract public function admin();

    /**
     * Connect to a database server
     *
     * @param string $server    The selected server
     * @param string $database  The database name
     * @param string $schema    The database schema
     *
     * @return array
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
     * @return ExportAdmin
     */
    protected function export()
    {
        if (!$this->exportAdmin) {
            $this->exportAdmin = new ExportAdmin();
            $this->exportAdmin->init($this->admin());
        }
        return $this->exportAdmin;
    }

    /**
     * Get data for export
     *
     * @param string $server        The selected server
     * @param string $database      The database name
     *
     * @return array
     */
    public function getExportOptions(string $server, string $database = '')
    {
        $this->connect($server, $database);

        $options = $this->package->getServerOptions($server);
        $breadcrumbs = [$options['name']];
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
