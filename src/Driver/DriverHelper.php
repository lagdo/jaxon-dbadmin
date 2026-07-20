<?php

namespace Lagdo\DbAdmin\Support\Driver;

use Lagdo\DbAdmin\Driver\Driver;
use Lagdo\DbAdmin\Driver\EngineInterface;
use Lagdo\DbAdmin\Driver\StatementInterface;
use Lagdo\DbAdmin\Driver\Utils\Utils;
use Lagdo\DbAdmin\Support\Driver\PageUi;

/**
 * Helper for driver proxies
 */
class DriverHelper
{
    /**
     * @var EngineInterface
     */
    private EngineInterface $engine;

    /**
     * @var StatementInterface
     */
    private StatementInterface $statement;

    /**
     * @param Utils $utils
     * @param PageUi $pageUi
     */
    public function __construct(private Utils $utils, private PageUi $pageUi)
    {}

    /**
     * @param Driver $driver
     *
     * @return self
     */
    public function setDriver(Driver $driver): self
    {
        $this->engine = $driver->engine;
        $this->statement = $driver->statement;
        return $this;
    }

    /**
     * @return EngineInterface
     */
    public function engine(): EngineInterface
    {
        return $this->engine;
    }

    /**
     * @return StatementInterface
     */
    public function statement(): StatementInterface
    {
        return $this->statement;
    }

    /**
     * @return PageUi
     */
    public function pageUi(): PageUi
    {
        return $this->pageUi;
    }

    /**
     * @return Utils
     */
    public function utils(): Utils
    {
        return $this->utils;
    }
}
