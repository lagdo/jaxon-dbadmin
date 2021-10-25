<?php

namespace Lagdo\DbAdmin\Db;

use Lagdo\DbAdmin\Driver\UtilInterface;
use Lagdo\DbAdmin\Driver\DriverInterface;

class Admin
{
    /**
     * @var Util
     */
    public $util;

    /**
     * @var DriverInterface
     */
    public $driver;

    /**
     * @var Translator
     */
    protected $trans;

    /**
     * The constructor
     *
     * @param Util $util
     * @param DriverInterface $driver
     * @param Translator $trans
     */
    public function __construct(Util $util, DriverInterface $driver, Translator $trans)
    {
        $this->util = $util;
        $this->driver = $driver;
        $this->trans = $trans;
    }
}
