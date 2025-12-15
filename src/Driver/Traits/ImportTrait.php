<?php

namespace Lagdo\DbAdmin\Db\Driver\Traits;

use Lagdo\DbAdmin\Db\Driver\Facades\ImportFacade;

/**
 * Facade to import functions
 */
trait ImportTrait
{
    use AbstractTrait;

    /**
     * Get the facade
     *
     * @return ImportFacade
     */
    protected function importFacade(): ImportFacade
    {
        return $this->di()->g(ImportFacade::class);
    }

    /**
     * Get data for import
     *
     * @return array
     */
    public function getImportOptions(): array
    {
        $this->connectToDatabase();
        $this->breadcrumbs(true)->item($this->utils->trans->lang('Import'));
        return $this->importFacade()->getImportOptions();
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
        return $this->importFacade()->executeSqlFiles($files, $errorStops, $onlyErrors);
    }
}
