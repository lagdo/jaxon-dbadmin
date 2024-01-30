<?php

namespace Lagdo\DbAdmin\Db\Select;

use Lagdo\DbAdmin\Db\AbstractFacade;

use Exception;

/**
 * Facade to table select functions
 */
trait SelectTrait
{
    /**
     * The proxy
     *
     * @var SelectFacade
     */
    protected $tableSelectFacade = null;

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
     * @return SelectFacade
     */
    protected function tableSelect(): SelectFacade
    {
        if (!$this->tableSelectFacade) {
            $this->tableSelectFacade = new SelectFacade();
            $this->tableSelectFacade->init($this->facade());
        }
        return $this->tableSelectFacade;
    }

    /**
     * Get required data for create/update on tables
     *
     * @param string $server The selected server
     * @param string $database The database name
     * @param string $schema The database schema
     * @param string $table The table name
     * @param array $queryOptions The query options
     *
     * @return array
     * @throws Exception
     */
    public function getSelectData(string $server, string $database, string $schema, string $table, array $queryOptions = []): array
    {
        $this->connect($server, $database, $schema);

        $package = $this->facade()->package;
        $this->setBreadcrumbs([$package->getServerName($server), $database,
            $this->trans->lang('Tables'), $table, $this->trans->lang('Select')]);

        $this->util->input()->table = $table;
        $this->util->input()->values = $queryOptions;
        return $this->tableSelect()->getSelectData($table, $queryOptions);
    }

    /**
     * Get required data for create/update on tables
     *
     * @param string $server The selected server
     * @param string $database The database name
     * @param string $schema The database schema
     * @param string $table The table name
     * @param array $queryOptions The query options
     *
     * @return array
     * @throws Exception
     */
    public function execSelect(string $server, string $database, string $schema, string $table, array $queryOptions = []): array
    {
        $this->connect($server, $database, $schema);

        $this->util->input()->table = $table;
        $this->util->input()->values = $queryOptions;
        return $this->tableSelect()->execSelect($table, $queryOptions);
    }
}
