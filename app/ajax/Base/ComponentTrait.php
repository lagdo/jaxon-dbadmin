<?php

namespace Lagdo\DbAdmin\Ajax\Base;

use Jaxon\Attributes\Attribute\Databag;
use Lagdo\DbAdmin\Ajax\Admin\Page\Breadcrumbs;
use Lagdo\DbAdmin\Ajax\Exception\AppException;
use Lagdo\DbAdmin\Db\Config\ServerConfig;
use Lagdo\DbAdmin\Db\Driver\DbFacade;
use Lagdo\DbAdmin\Db\Translator;
use Lagdo\DbAdmin\Ui\Tab;
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
     * @param string $bag
     * @param string $key
     * @param mixed $value
     *
     * @return void
     */
    protected function setBag(string $bag, string $key, $value): void
    {
        $currentTab = Tab::current();
        $currentValue = $this->bag($bag)->get($currentTab, []);
        $this->bag($bag)->set($currentTab, [
            ...$currentValue,
            $key => $value,
        ]);
    }

    /**
     * @param string $bag
     * @param string $key
     * @param mixed $value
     *
     * @return mixed
     */
    protected function getBag(string $bag, string $key, $value = null): mixed
    {
        $currentValue = $this->bag($bag)->get(Tab::current(), []);
        return $currentValue[$key] ?? $value;
    }

    /**
     * @param array
     *
     * @return void
     */
    protected function setCurrentDb(array $currentDb): void
    {
        $this->setBag('dbadmin', 'db', $currentDb);
    }

    /**
     * @return array
     */
    protected function getCurrentDb(): array
    {
        return $this->getBag('dbadmin', 'db', []);
    }

    /**
     * @param string $id
     *
     * @return string
     */
    protected function tabId(string $id): string
    {
        return Tab::id($id);
    }

    /**
     * @param string $key
     *
     * @return string
     */
    protected function tabBag(string $key): string
    {
        return "{$key}." . Tab::current();
    }

    /**
     * @param string $queryDivId
     *
     * @return void
     */
    protected function setupSqlEditor(string $queryDivId): void
    {
        [$server, ] = $this->getCurrentDb();
        $driver = $this->config()->getServerDriver($server);
        $this->response()->jo('jaxon.dbadmin')->createSqlSelectEditor($queryDivId, $driver);
    }
}
