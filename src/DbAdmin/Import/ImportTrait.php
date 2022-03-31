<?php

namespace Lagdo\DbAdmin\DbAdmin\Import;

use Lagdo\DbAdmin\DbAdmin\AbstractAdmin;
use Exception;

/**
 * Admin import functions
 */
trait ImportTrait
{
    /**
     * The proxy
     *
     * @var ImportAdmin
     */
    protected $importAdmin = null;

    /**
     * @return AbstractAdmin
     */
    abstract public function admin(): AbstractAdmin;

    /**
     * Connect to a database server
     *
     * @param string $server    The selected server
     * @param string $database  The database name
     * @param string $schema    The database schema
     *
     * @return array
     */
    abstract public function connect(string $server, string $database = '', string $schema = ''): array;

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
     * @return ImportAdmin
     */
    protected function import(): ImportAdmin
    {
        if (!$this->importAdmin) {
            $this->importAdmin = new ImportAdmin();
            $this->importAdmin->init($this->admin());
        }
        return $this->importAdmin;
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

        $options = $this->package->getServerOptions($server);
        $breadcrumbs = [$options['name']];
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
