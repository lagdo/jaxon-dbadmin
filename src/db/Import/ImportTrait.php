<?php

namespace Lagdo\DbAdmin\Db\Import;

use Jaxon\Di\Container;

/**
 * Facade to import functions
 */
trait ImportTrait
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
     * @return ImportFacade
     */
    protected function import(): ImportFacade
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

        $this->setBreadcrumbs(!!$this->dbName, [$this->trans->lang('Import')]);

        return $this->import()->getImportOptions();
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
        return $this->import()->executeSqlFiles($files, $errorStops, $onlyErrors);
    }
}
