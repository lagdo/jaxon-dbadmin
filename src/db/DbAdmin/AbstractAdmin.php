<?php

namespace Lagdo\DbAdmin\Db\DbAdmin;

use Lagdo\DbAdmin\Driver\DriverInterface;
use Lagdo\DbAdmin\Util;
use Lagdo\DbAdmin\Translator;

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
     * @var Translator
     */
    public $trans = null;

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
        $this->trans = $dbAdmin->trans;
    }
}
