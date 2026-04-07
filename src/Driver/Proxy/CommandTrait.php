<?php

namespace Lagdo\DbAdmin\Db\Driver\Proxy;

use Lagdo\DbAdmin\Db\Driver\AbstractProxyTrait;

/**
 * Proxy to command functions
 */
trait CommandTrait
{
    use AbstractProxyTrait;

    /**
     * Get the proxy
     *
     * @return CommandProxy
     */
    protected function commandProxy(): CommandProxy
    {
        return $this->di()->g(CommandProxy::class);
    }

    /**
     * Prepare a query
     *
     * @return void
     */
    public function prepareCommand()
    {
        $this->breadcrumbs(true)->item($this->utils()->lang('Query'));
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
        return $this->commandProxy()->executeCommands($query, $limit, $errorStops, $onlyErrors);
    }
}
