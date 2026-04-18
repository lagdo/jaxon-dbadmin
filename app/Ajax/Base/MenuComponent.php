<?php

namespace Lagdo\DbAdmin\App\Ajax\Base;

use Jaxon\App\Component;
use Jaxon\App\ComponentDataTrait;
use Lagdo\DbAdmin\App\Ui\MenuBuilder;
use Lagdo\DbAdmin\App\Ui\Tab\Tab;
use Lagdo\DbAdmin\Support\Config\ConfigProvider;
use Lagdo\DbAdmin\Support\Driver\DriverProxy;
use Lagdo\DbAdmin\Support\Translator;

abstract class MenuComponent extends Component
{
    use ComponentDataTrait;
    use TabItemTrait;

    /**
     * @param MenuBuilder $ui
     * @param DriverProxy $db
     * @param Translator $trans
     * @param ConfigProvider $config
     * @param Tab $tab
     */
    public function __construct(private MenuBuilder $ui, private DriverProxy $db,
        private Translator $trans, private ConfigProvider $config, private Tab $tab)
    {}

    /**
     * @return DriverProxy
     */
    protected function db(): DriverProxy
    {
        return $this->db;
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
     * @return ConfigProvider
     */
    protected function config(): ConfigProvider
    {
        return $this->config;
    }
}
