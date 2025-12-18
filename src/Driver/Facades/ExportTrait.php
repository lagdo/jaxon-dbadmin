<?php

namespace Lagdo\DbAdmin\Db\Driver\Facades;

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
     * @return array
     */
    public function getExportOptions(): array
    {
        $this->connectToDatabase();
        $this->breadcrumbs(true)->item($this->utils->trans->lang('Export'));
        return $this->exportFacade()->getExportOptions($this->dbName);
    }

    /**
     * @return array
     */
    public function getSelectValues(): array
    {
        $this->connectToServer();
        return $this->exportFacade()->getSelectValues();
    }

    /**
     * Export databases
     * The databases and tables parameters are array where the keys are names and the values
     * are boolean which indicate whether the corresponding data should be exported too.
     *
     * @param array  $databases     The databases to dump
     * @param array  $dumpOptions   The export options
     *
     * @return array|string
     */
    public function exportDatabases(array $databases, array $dumpOptions)
    {
        $this->connectToServer();
        return $this->exportFacade()->exportDatabases($databases, $dumpOptions);
    }
}
