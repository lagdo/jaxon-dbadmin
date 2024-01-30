<?php

namespace Lagdo\DbAdmin\Db\Import;

use Lagdo\DbAdmin\Db\AbstractFacade;
use Exception;

/**
 * Facade to import functions
 */
trait ImportTrait
{
    /**
     * The proxy
     *
     * @var ImportFacade
     */
    protected $importFacade = null;

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
     * @return ImportFacade
     */
    protected function import(): ImportFacade
    {
        if (!$this->importFacade) {
            $this->importFacade = new ImportFacade();
            $this->importFacade->init($this->facade());
        }
        return $this->importFacade;
    }

    /**
     * Get data for import
     *
     * @param string $server        The selected server
     * @param string $database      The database name
     *
     * @return array
     */
    public function getImportOptions(string $server, string $database = ''): array
    {
        $this->connect($server, $database);

        $package = $this->facade()->package;
        $breadcrumbs = [$package->getServerName($server)];
        if (($database)) {
            $breadcrumbs[] = $database;
        }
        $breadcrumbs[] = $this->trans->lang('Import');
        $this->setBreadcrumbs($breadcrumbs);

        return $this->import()->getImportOptions();
    }

    /**
     * Run queries from uploaded files
     *
     * @param string $server        The selected server
     * @param array  $files         The uploaded files
     * @param bool   $errorStops    Stop executing the requests in case of error
     * @param bool   $onlyErrors    Return only errors
     * @param string $database      The database name
     * @param string $schema        The database schema
     *
     * @return array
     */
    public function executeSqlFiles(string $server, array $files, bool $errorStops, bool $onlyErrors,
        string $database = '', string $schema = ''): array
    {
        $this->connect($server, $database, $schema);
        return $this->import()->executeSqlFiles($files, $errorStops, $onlyErrors);
    }
}
