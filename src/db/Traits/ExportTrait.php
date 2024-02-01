<?php

namespace Lagdo\DbAdmin\Db\Traits;

use Lagdo\DbAdmin\Db\Facades\ExportFacade;

/**
 * Facade to export functions
 */
trait ExportTrait
{
    use AbstractTrait;

    /**
     * Get the facade
     *
     * @return ExportFacade
     */
    protected function exportFacade(): ExportFacade
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
        $this->bcdb()->breadcrumb($this->trans->lang('Export'));
        return $this->exportFacade()->getExportOptions($database);
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
        return $this->exportFacade()->exportDatabases($databases, $tables, $dumpOptions);
    }
}
