<?php

namespace Lagdo\DbAdmin\App\Ajax\Base;

use Jaxon\App\Component;
use Jaxon\App\ComponentDataTrait;
use Lagdo\DbAdmin\App\Ui\MenuBuilder;
use Lagdo\DbAdmin\App\Ui\Tab\Tab;
use Lagdo\DbAdmin\Support\Driver\DriverProxy;
use Lagdo\DbAdmin\Support\Provider\DatabaseConfigProvider;
use Lagdo\DbAdmin\Support\Translator;

abstract class MenuComponent extends Component
{
    use ComponentDataTrait;
    use TabItemTrait;

    /**
     * @param MenuBuilder $ui
     * @param DriverProxy $driver
     * @param Translator $trans
     * @param DatabaseConfigProvider $config
     * @param Tab $tab
     */
    public function __construct(private MenuBuilder $ui, private DriverProxy $driver,
        private Translator $trans, private DatabaseConfigProvider $config, private Tab $tab)
    {}

    /**
     * @return DriverProxy
     */
    protected function driver(): DriverProxy
    {
        return $this->driver;
    }

    /**
     * @return MenuBuilder
     */
    protected function ui(): MenuBuilder
    {
        return $this->ui;
    }

    /**
     * @return Tab
     */
    protected function tab(): Tab
    {
        return $this->tab;
    }

    /**
     * @return Translator
     */
    protected function trans(): Translator
    {
        return $this->trans;
    }

    /**
     * @return DatabaseConfigProvider
     */
    protected function config(): DatabaseConfigProvider
    {
        return $this->config;
    }
}
