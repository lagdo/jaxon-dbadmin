<?php

namespace Lagdo\DbAdmin\Db\Command;

use Jaxon\Di\Container;

/**
 * Facade to command functions
 */
trait CommandTrait
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
     * @return CommandFacade
     */
    protected function command(): CommandFacade
    {
        return $this->di()->g(CommandFacade::class);
    }

    /**
     * Prepare a query
     *
     * @return array
     */
    public function prepareCommand(): array
    {
        $this->connectToSchema();

        $this->setBreadcrumbs(!!$this->dbName, [$this->trans->lang('Query')]);

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
        $this->connectToSchema();
        return $this->command()->executeCommands($query, $limit, $errorStops, $onlyErrors);
    }
}
