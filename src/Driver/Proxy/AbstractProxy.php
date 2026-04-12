<?php

namespace Lagdo\DbAdmin\Db\Driver\Proxy;

use Lagdo\DbAdmin\Db\Driver\DriverHelperTrait;
use Lagdo\DbAdmin\Db\Driver\DriverHelper;

/**
 * Common attributes for all facades
 */
class AbstractProxy
{
    use DriverHelperTrait;

    /**
     * @param DriverHelper $driverHelper
     */
    public function __construct(DriverHelper $driverHelper)
    {
        $this->driverHelper = $driverHelper;
    }
}
