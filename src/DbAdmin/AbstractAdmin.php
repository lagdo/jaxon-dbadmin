<?php

namespace Lagdo\DbAdmin\DbAdmin;

use Lagdo\DbAdmin\Driver\DriverInterface;
use Lagdo\DbAdmin\Db\Util;

/**
 * Common attributes for all admins
 */
class AbstractAdmin
{
    /**
     * @var DriverInterface
     */
    public $driver = null;

    /**
     * @var Util
     */
    public $util = null;

    /**
     * Initialize the facade
     *
     * @param AbstractAdmin $dbAdmin
     *
     * @return void
     */
    public function init(AbstractAdmin $dbAdmin)
    {
        $this->driver = $dbAdmin->driver;
        $this->util = $dbAdmin->util;
    }
}
