<?php

namespace Lagdo\DbAdmin\Ajax;

use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\Ajax\Admin\Page\Breadcrumbs;
use Lagdo\DbAdmin\Ajax\Exception\AppException;
use Lagdo\DbAdmin\Db\Config\ServerConfig;
use Lagdo\DbAdmin\Db\Driver\DbFacade;
use Lagdo\DbAdmin\Db\Translator;
use Lagdo\DbAdmin\Ui\UiBuilder;
use Exception;

/**
 * Common functions for component classes
 */
#[Databag('dbadmin')]
trait ComponentTrait
{
    /**
     * @var ServerConfig
     */
    protected ServerConfig $config;

    /**
     * @var DbFacade
     */
    protected DbFacade $db;

    /**
     * @var UiBuilder
     */
    protected UiBuilder $ui;

    /**
     * @var Translator
     */
    protected Translator $trans;

    /**
     * @return ServerConfig
     */
    protected function config(): ServerConfig
    {
        return $this->config;
    }

    /**
     * @return DbFacade
     */
    protected function db(): DbFacade
    {
        return $this->db;
    }

    /**
     * @return UiBuilder
     */
    protected function ui(): UiBuilder
    {
        return $this->ui;
    }

    /**
     * @return Translator
     */
    protected function trans(): Translator
    {
        return $this->trans;
    }

    /**
     * @throws Exception
     * @return never
     */
    protected function notYetAvailable(): void
    {
        throw new AppException($this->trans->lang('This feature is not yet available'));
    }

    /**
     * Show breadcrumbs
     *
     * @return void
     */
    protected function showBreadcrumbs(): void
    {
        $this->cl(Breadcrumbs::class)->render();
    }

    /**
     * @param string $queryDivId
     *
     * @return void
     */
    protected function setupSqlEditor(string $queryDivId): void
    {
        [$server, ] = $this->bag('dbadmin')->get('db');
        $driver = $this->config()->getServerDriver($server);
        $this->response()->jo('jaxon.dbadmin')->createSqlSelectEditor($queryDivId, $driver);
    }
}
