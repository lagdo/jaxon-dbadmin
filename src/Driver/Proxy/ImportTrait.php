<?php

namespace Lagdo\DbAdmin\Db\Driver\Proxy;

/**
 * Proxy to import functions
 */
trait ImportTrait
{
    use AbstractTrait;

    /**
     * Get the proxy
     *
     * @return ImportProxy
     */
    protected function importProxy(): ImportProxy
    {
        return $this->di()->g(ImportProxy::class);
    }

    /**
     * Get data for import
     *
     * @return array
     */
    public function getImportOptions(): array
    {
        $this->connectToDatabase();
        $this->breadcrumbs(true)->item($this->utils()->lang('Import'));
        return $this->importProxy()->getImportOptions();
    }

    /**
     * Run queries from uploaded files
     *
     * @param array  $files         The uploaded files
     * @param bool   $errorStops    Stop executing the requests in case of error
     * @param bool   $onlyErrors    Return only errors
     *
     * @return array
     */
    public function executeSqlFiles(array $files, bool $errorStops, bool $onlyErrors): array
    {
        $this->connectToSchema();
        return $this->importProxy()->executeSqlFiles($files, $errorStops, $onlyErrors);
    }
}
