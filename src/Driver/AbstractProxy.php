<?php

namespace Lagdo\DbAdmin\Db\Driver;

use Lagdo\DbAdmin\Db\UiData\AppPage;
use Lagdo\DbAdmin\Support\DriverInterface;
use Lagdo\DbAdmin\Support\GrammarInterface;
use Lagdo\DbAdmin\Support\Utils\Utils;

/**
 * Common attributes for all facades
 */
class AbstractProxy
{
    /**
     * @var GrammarInterface|null
     */
    private GrammarInterface|null $grammar = null;

    /**
     * @param DriverInterface|null $driver
     * @param AppPage $page
     * @param Utils $utils
     */
    public function __construct(private DriverInterface|null $driver,
        private AppPage $page, private Utils $utils)
    {
        $this->grammar = $driver?->grammar() ?? null;
    }

    /**
     * @param DriverInterface|null $driver
     *
     * @return self
     */
    protected function setDriver(DriverInterface|null $driver = null): self
    {
        $this->driver = $driver;
        $this->grammar = $driver?->grammar() ?? null;
        return $this;
    }

    /**
     * @param AppPage $page
     *
     * @return self
     */
    protected function setPage(AppPage $page): self
    {
        $this->page = $page;
        return $this;
    }

    /**
     * @param Utils $utils
     *
     * @return self
     */
    protected function setUtils(Utils $utils): self
    {
        $this->utils = $utils;
        return $this;
    }

    /**
     * @return DriverInterface|null
     */
    protected function driver(): DriverInterface|null
    {
        return $this->driver;
    }

    /**
     * @return GrammarInterface|null
     */
    protected function grammar(): GrammarInterface|null
    {
        return $this->grammar;
    }

    /**
     * @return AppPage
     */
    protected function page(): AppPage
    {
        return $this->page;
    }

    /**
     * @return Utils
     */
    protected function utils(): Utils
    {
        return $this->utils;
    }
}
