<?php

namespace Lagdo\DbAdmin\App\Ajax\Base;

use Jaxon\App\ComponentDataTrait;
use Jaxon\Attributes\Attribute\Databag;
use Jaxon\Attributes\Attribute\Inject;
use Lagdo\DbAdmin\App\Ajax\Admin\Page\Breadcrumbs;
use Lagdo\DbAdmin\App\Ajax\Exception\AppException;
use Lagdo\DbAdmin\Support\Config\ServerConfig;
use Lagdo\DbAdmin\Support\Driver\DriverProxy;
use Lagdo\DbAdmin\Support\Translator;
use Lagdo\DbAdmin\App\Ui\Tab\Tab;
use Lagdo\DbAdmin\App\Ui\UiBuilder;
use Exception;

use function array_filter;

/**
 * Common functions for component classes
 */
#[Databag('dbadmin')]
trait ComponentTrait
{
    use ComponentDataTrait;

    /**
     * @var ServerConfig
     */
    #[Inject]
    protected ServerConfig $config;

    /**
     * @var DriverProxy
     */
    #[Inject]
    protected DriverProxy $db;

    /**
     * @var Translator
     */
    #[Inject]
    protected Translator $trans;

    /**
     * @var UiBuilder
     */
    #[Inject]
    protected UiBuilder $ui;

    /**
     * @var Tab
     */
    #[Inject]
    protected Tab $tab;

    /**
     * @return ServerConfig
     */
    protected function config(): ServerConfig
    {
        return $this->config;
    }

    /**
     * @return DriverProxy
     */
    protected function db(): DriverProxy
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
     * @return Tab
     */
    protected function tab(): Tab
    {
        return $this->tab;
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
     * @param string|null $server
     *
     * @return bool
     */
    protected function hasServerAccess(string|null $server = null): bool
    {
        if ($server === null) {
            $server = $this->getCurrentDb()[0] ?? '';
        }
        return $this->config()->getServerAccess($server);
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
     * @return mixed
     */
    protected function getBag(string $bag, string $key, mixed $value = null): mixed
    {
        $currentValues = $this->bag($bag)->get($key, []);
        return $currentValues[$this->tab()->app()->current()] ?? $value;
    }

    /**
     * @param string $bag
     * @param string $key
     * @param mixed $value
     *
     * @return void
     */
    protected function setBag(string $bag, string $key, mixed $value): void
    {
        $currentValues = $this->bag($bag)->get($key, []);
        $this->bag($bag)->set($key, [
            ...$currentValues,
            $this->tab()->app()->current() => $value,
        ]);
    }

    /**
     * @param string $bag
     * @param string $key
     *
     * @return void
     */
    protected function unsetBag(string $bag, string $key): void
    {
        $currentTab = $this->tab()->app()->current();
        $nextValues = array_filter($this->bag($bag)->get($key, []),
            fn(string $tab) => $tab !== $currentTab, ARRAY_FILTER_USE_KEY);
        $this->bag($bag)->set($key, $nextValues);
    }

    /**
     * @param array $currentDb
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
     * @return void
     */
    protected function unsetCurrentDb(): void
    {
        $this->unsetBag('dbadmin', 'db');
    }

    /**
     * @return string
     */
    protected function getCurrentTitle(): string
    {
        return $this->getBag('dbadmin', 'title', '');
    }

    /**
     * @param string $currentTitle
     *
     * @return void
     */
    protected function setCurrentTitle(string $currentTitle): void
    {
        $this->setBag('dbadmin', 'title', $currentTitle);
    }

    /**
     * @param string $id
     *
     * @return string
     */
    protected function tabId(string $id): string
    {
        return $this->tab()->app()->id($id);
    }

    /**
     * @param string $key
     *
     * @return string
     */
    protected function tabBag(string $key): string
    {
        return "{$key}." . $this->tab()->app()->current();
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
        $this->response()->jo('jaxon.dbadmin')->createSelectEditor($queryDivId, $driver);
    }
}
