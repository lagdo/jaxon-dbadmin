<?php

namespace Lagdo\DbAdmin\Db\Driver;

use Lagdo\DbAdmin\Db\UiData\AppPage;
use Lagdo\DbAdmin\Driver\EngineInterface;
use Lagdo\DbAdmin\Driver\StatementInterface;
use Lagdo\DbAdmin\Driver\Utils\Utils;

/**
 * Common attributes for all facades
 */
trait DriverHelperTrait
{
    /**
     * @var DriverHelper
     */
    private DriverHelper $driverHelper;

    /**
     * @return DriverHelper
     */
    public function helper(): DriverHelper
    {
        return $this->driverHelper;
    }

    /**
     * @return EngineInterface|null
     */
    protected function engine(): EngineInterface|null
    {
        return $this->driverHelper->engine();
    }

    /**
     * @return StatementInterface|null
     */
    protected function statement(): StatementInterface|null
    {
        return $this->driverHelper->statement();
    }

    /**
     * @return AppPage
     */
    protected function page(): AppPage
    {
        return $this->driverHelper->page();
    }

    /**
     * @return Utils
     */
    protected function utils(): Utils
    {
        return $this->driverHelper->utils();
    }
}
