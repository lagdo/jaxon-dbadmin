<?php

namespace Lagdo\DbAdmin\App;

use Jaxon\App\Component;
use Lagdo\DbAdmin\Driver\DriverInterface;
use Lagdo\DbAdmin\Translator;
use Lagdo\DbAdmin\App\Ui\MenuBuilder;

abstract class MenuComponent extends Component
{
    /**
     * @var MenuBuilder
     */
    protected $ui;

    /**
     * @var Translator
     */
    protected $trans;

    /**
     * @var DriverInterface
     */
    protected $driver;

    /**
     * @param MenuBuilder $ui
     * @param Translator $trans
     * @param DriverInterface $driver
     */
    public function __construct(MenuBuilder $ui, Translator $trans, DriverInterface $driver)
    {
        $this->ui = $ui;
        $this->trans = $trans;
        $this->driver = $driver;
    }
}
