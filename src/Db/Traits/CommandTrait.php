<?php

namespace Lagdo\DbAdmin\Db\Traits;

use Lagdo\DbAdmin\Db\Facades\CommandFacade;

/**
 * Facade to command functions
 */
trait CommandTrait
{
    use AbstractTrait;

    /**
     * Get the facade
     *
     * @return CommandFacade
     */
    protected function commandFacade(): CommandFacade
    {
        return $this->di()->g(CommandFacade::class);
    }

    /**
     * Prepare a query
     *
     * @return void
     */
    public function prepareCommand()
    {
        $this->breadcrumbs(true)->item($this->utils->trans->lang('Query'));
    }

    /**
     * Execute a query
     *
     * @param string $query         The query to be executed
     * @param int    $limit         The max number of rows to return
     * @param bool   $errorStops    Stop executing the requests in case of error
     * @param bool   $onlyErrors    Return only errors
     *
     * @return array
     */
    public function executeCommands(string $query, int $limit, bool $errorStops, bool $onlyErrors): array
    {
        $this->connectToSchema();
        return $this->commandFacade()->executeCommands($query, $limit, $errorStops, $onlyErrors);
    }
}
