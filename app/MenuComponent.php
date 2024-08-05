<?php

namespace Lagdo\DbAdmin\App;

use Jaxon\App\Component;
use Lagdo\DbAdmin\Driver\DriverInterface;
use Lagdo\DbAdmin\Translator;
use Lagdo\DbAdmin\Ui\MenuBuilder;

abstract class MenuComponent extends Component
{
    /**
     * @param MenuBuilder $ui
     * @param Translator $trans
     * @param DriverInterface $driver
     */
    public function __construct(private MenuBuilder $ui, private Translator $trans,
        private DriverInterface $driver)
    {}
}
