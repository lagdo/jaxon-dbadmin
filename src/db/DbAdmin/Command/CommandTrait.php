<?php

namespace Lagdo\DbAdmin\Db\DbAdmin\Command;

use Lagdo\DbAdmin\Db\DbAdmin\AbstractAdmin;
use Exception;

/**
 * Admin command functions
 */
trait CommandTrait
{
    /**
     * The proxy
     *
     * @var CommandAdmin
     */
    protected $commandAdmin = null;

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
     * @return CommandAdmin
     */
    protected function command(): CommandAdmin
    {
        if (!$this->commandAdmin) {
            $this->commandAdmin = new CommandAdmin();
            $this->commandAdmin->init($this->admin());
        }
        return $this->commandAdmin;
    }

    /**
     * Prepare a query
     *
     * @param string $server        The selected server
     * @param string $database      The database name
     * @param string $schema        The database schema
     *
     * @return array
     */
    public function prepareCommand(string $server, string $database = '', string $schema = ''): array
    {
        $this->connect($server, $database, $schema);

        $package = $this->admin()->package;
        $breadcrumbs = [$package->getServerName($server)];
        if (($database)) {
            $breadcrumbs[] = $database;
        }
        $breadcrumbs[] = $this->trans->lang('Query');
        $this->setBreadcrumbs($breadcrumbs);

        $labels = [
            'execute' => $this->trans->lang('Execute'),
            'limit_rows' => $this->trans->lang('Limit rows'),
            'error_stops' => $this->trans->lang('Stop on error'),
            'only_errors' => $this->trans->lang('Show only errors'),
        ];

        return ['labels' => $labels];
    }

    /**
     * Execute a query
     *
     * @param string $server        The selected server
     * @param string $query         The query to be executed
     * @param int    $limit         The max number of rows to return
     * @param bool   $errorStops    Stop executing the requests in case of error
     * @param bool   $onlyErrors    Return only errors
     * @param string $database      The database name
     * @param string $schema        The database schema
     *
     * @return array
     */
    public function executeCommands(
        string $server,
        string $query,
        int $limit,
        bool $errorStops,
        bool $onlyErrors,
        string $database = '',
        string $schema = ''
    ): array
    {
        $this->connect($server, $database, $schema);
        return $this->command()->executeCommands($query, $limit, $errorStops, $onlyErrors);
    }
}
