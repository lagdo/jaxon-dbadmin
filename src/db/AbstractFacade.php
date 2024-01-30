<?php

namespace Lagdo\DbAdmin\Db;

use Lagdo\DbAdmin\App\Package;
use Lagdo\DbAdmin\Driver\DriverInterface;
use Lagdo\DbAdmin\Util;
use Lagdo\DbAdmin\Translator;

/**
 * Common attributes for all admins
 */
class AbstractFacade
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
     * The Jaxon DbAdmin package
     *
     * @var Package
     */
    public $package;

    /**
     * Initialize the facade
     *
     * @param AbstractFacade $dbAdmin
     *
     * @return void
     */
    public function init(AbstractFacade $dbAdmin)
    {
        $this->driver = $dbAdmin->driver;
        $this->util = $dbAdmin->util;
        $this->trans = $dbAdmin->trans;
        $this->package = $dbAdmin->package;
    }
}