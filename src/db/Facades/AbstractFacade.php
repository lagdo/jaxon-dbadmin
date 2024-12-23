<?php

namespace Lagdo\DbAdmin\Db\Facades;

use Lagdo\DbAdmin\Admin\Admin;
use Lagdo\DbAdmin\Driver\DriverInterface;
use Lagdo\DbAdmin\Package;
use Lagdo\DbAdmin\Translator;

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
     * @var Admin
     */
    public $admin = null;

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
        $this->admin = $dbFacade->admin;
        $this->trans = $dbFacade->trans;
        $this->package = $dbFacade->package;
    }
}
