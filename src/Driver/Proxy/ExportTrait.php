<?php

namespace Lagdo\DbAdmin\Db\Driver\Proxy;

/**
 * Proxy to export functions
 */
trait ExportTrait
{
    use AbstractTrait;

    /**
     * Get the proxy
     *
     * @return ExportProxy
     */
    protected function exportProxy(): ExportProxy
    {
        return $this->di()->g(ExportProxy::class);
    }

    /**
     * Get data for export
     *
     * @return array
     */
    public function getExportOptions(): array
    {
        $this->connectToDatabase();
        $this->breadcrumbs(true)->item($this->utils()->lang('Export'));
        return $this->exportProxy()->getExportOptions($this->dbName);
    }

    /**
     * @return array
     */
    public function getSelectValues(): array
    {
        $this->connectToServer();
        return $this->exportProxy()->getSelectValues();
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
        return $this->exportProxy()->exportDatabases($databases, $dumpOptions);
    }
}
