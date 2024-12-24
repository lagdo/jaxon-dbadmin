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
     * @return array
     */
    public function prepareCommand(): array
    {
        $this->bcdb()->breadcrumb($this->utils->trans->lang('Query'));
        $labels = [
            'execute' => $this->utils->trans->lang('Execute'),
            'limit_rows' => $this->utils->trans->lang('Limit rows'),
            'error_stops' => $this->utils->trans->lang('Stop on error'),
            'only_errors' => $this->utils->trans->lang('Show only errors'),
        ];
        return ['labels' => $labels];
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
