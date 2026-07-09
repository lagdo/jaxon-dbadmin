<?php

namespace Lagdo\DbAdmin\Support\Driver;

use Lagdo\DbAdmin\Support\Driver\DriverHelper;
use Lagdo\DbAdmin\Support\Driver\PageUi;
use Lagdo\DbAdmin\Driver\EngineInterface;
use Lagdo\DbAdmin\Driver\StatementInterface;
use Lagdo\DbAdmin\Driver\Utils\Utils;

/**
 * Base class for those who need to call the driver functions.
 * The helper class and trait provide access to the engine,
 * statement, ui dto and utils class instances.
 */
abstract class AbstractDriverProxy
{
    /**
     * @var CurrentDbDto
     */
    protected CurrentDbDto $currentDb;

    /**
     * @var DriverHelper
     */
    protected DriverHelper $driverHelper;

    /**
     * @param AbstractDriverProxy $parent
     */
    public function __construct(AbstractDriverProxy $parent)
    {
        $this->currentDb = $parent->currentDb;
        $this->driverHelper = $parent->driverHelper;
    }

    /**
     * @return CurrentDbDto
     */
    public function currentDb(): CurrentDbDto
    {
        return $this->currentDb;
    }

    /**
     * @return DriverHelper
     */
    public function helper(): DriverHelper
    {
        return $this->driverHelper;
    }

    /**
     * @return EngineInterface
     */
    protected function engine(): EngineInterface
    {
        return $this->driverHelper->engine();
    }

    /**
     * @return StatementInterface
     */
    protected function statement(): StatementInterface
    {
        return $this->driverHelper->statement();
    }

    /**
     * @return PageUi
     */
    protected function pageUi(): PageUi
    {
        return $this->driverHelper->pageUi();
    }

    /**
     * @return Utils
     */
    protected function utils(): Utils
    {
        return $this->driverHelper->utils();
    }
}
