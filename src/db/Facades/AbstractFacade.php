<?php

namespace Lagdo\DbAdmin\Db\Facades;

use Lagdo\DbAdmin\Driver\DriverInterface;
use Lagdo\DbAdmin\Package;
use Lagdo\DbAdmin\Translator;
use Lagdo\DbAdmin\Util;

/**
 * Common attributes for all facades
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
     * @param AbstractFacade $dbFacade
     *
     * @return void
     */
    public function __construct(AbstractFacade $dbFacade)
    {
        $this->driver = $dbFacade->driver;
        $this->util = $dbFacade->util;
        $this->trans = $dbFacade->trans;
        $this->package = $dbFacade->package;
    }
}
