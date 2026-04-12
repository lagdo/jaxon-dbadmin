<?php

namespace Lagdo\DbAdmin\Db\Driver;

use Lagdo\DbAdmin\Db\UiData\AppPage;
use Lagdo\DbAdmin\Driver\Driver;
use Lagdo\DbAdmin\Driver\EngineInterface;
use Lagdo\DbAdmin\Driver\StatementInterface;
use Lagdo\DbAdmin\Driver\Utils\Utils;

/**
 * Helper for driver proxies
 */
class DriverHelper
{
    /**
     * @var EngineInterface|null
     */
    private EngineInterface|null $engine = null;

    /**
     * @var StatementInterface|null
     */
    private StatementInterface|null $statement = null;

    /**
     * @var AppPage
     */
    private AppPage $page;

    /**
     * @var Utils
     */
    private Utils $utils;

    /**
     * @param Driver|null $driver
     *
     * @return self
     */
    public function setDriver(Driver|null $driver = null): self
    {
        if($driver !== null) {
            $this->engine = $driver->engine;
            $this->statement = $driver->statement;
        }
        return $this;
    }

    /**
     * @param AppPage $page
     *
     * @return self
     */
    public function setPage(AppPage $page): self
    {
        $this->page = $page;
        return $this;
    }

    /**
     * @param Utils $utils
     *
     * @return self
     */
    public function setUtils(Utils $utils): self
    {
        $this->utils = $utils;
        return $this;
    }

    /**
     * @return EngineInterface|null
     */
    public function engine(): EngineInterface|null
    {
        return $this->engine;
    }

    /**
     * @return StatementInterface|null
     */
    public function statement(): StatementInterface|null
    {
        return $this->statement;
    }

    /**
     * @return AppPage
     */
    public function page(): AppPage
    {
        return $this->page;
    }

    /**
     * @return Utils
     */
    public function utils(): Utils
    {
        return $this->utils;
    }
}
