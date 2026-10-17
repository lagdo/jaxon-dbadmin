<?php

namespace Lagdo\DbAdmin\Support\Driver;

use Jaxon\App\View\ViewRenderer;
use Jaxon\Config\ConfigSetter;
use Jaxon\Di\Container;
use Lagdo\DbAdmin\Driver\Driver;
use Lagdo\DbAdmin\Driver\EngineInterface;
use Lagdo\DbAdmin\Driver\StatementInterface;
use Lagdo\DbAdmin\Driver\Utils\Utils;
use Lagdo\DbAdmin\Support\DiAlias\ServerConfig;
use Lagdo\DbAdmin\Support\DiAlias\ServerDriver;
use Lagdo\DbAdmin\Support\Driver\PageUi;
use Lagdo\DbAdmin\Support\Provider\DatabaseConfigProvider;
use Lagdo\DbAdmin\Support\Translator;

use function Jaxon\jaxon;

/**
 * Helper for driver proxies
 */
class DriverHelper
{
    /**
     * @var EngineInterface
     */
    private EngineInterface $engine;

    /**
     * @var StatementInterface
     */
    private StatementInterface $statement;

    /**
     * @param Utils $utils
     * @param PageUi $pageUi
     */
    public function __construct(private Utils $utils, private PageUi $pageUi)
    {}

    /**
     * @param Driver $driver
     *
     * @return self
     */
    public function setDriver(Driver $driver): self
    {
        $this->engine = $driver->engine;
        $this->statement = $driver->statement;
        return $this;
    }

    /**
     * @return EngineInterface
     */
    public function engine(): EngineInterface
    {
        return $this->engine;
    }

    /**
     * @return StatementInterface
     */
    public function statement(): StatementInterface
    {
        return $this->statement;
    }

    /**
     * @return PageUi
     */
    public function pageUi(): PageUi
    {
        return $this->pageUi;
    }

    /**
     * @return Utils
     */
    public function utils(): Utils
    {
        return $this->utils;
    }

    /**
     * @return void
     */
    public static function setupDriver(): void
    {
        $di = jaxon()->di();

        // Register a driver for each database server.
        $configProvider = $di->g(DatabaseConfigProvider::class);
        foreach($configProvider->getServerIds() as $server) {
            // The driver options
            $di->set("dbadmin_server_options_$server", fn() =>
                $configProvider->getServerConfig($server));
            // The driver itself
            $di->set("dbadmin_server_$server", function() use($di, $server) {
                $utils = $di->g(Utils::class);
                $options = $di->g("dbadmin_server_options_$server");
                return Driver::createDriver($utils, $options);
            });
        }
        // Selected database driver options
        $di->set(ServerConfig::class, function(Container $di) {
            $setter = $di->g(ConfigSetter::class);
            $server = $di->g('dbadmin_config_server');
            return $setter->newConfig($di->g("dbadmin_server_options_$server"));
        });
        // Selected database driver
        $di->set(ServerDriver::class, function(Container $di) {
            $server = $di->g('dbadmin_config_server');
            return $di->g("dbadmin_server_$server");
        });

        // Make the translator available into views.
        $viewRenderer = $di->g(ViewRenderer::class);
        $viewRenderer->share('trans', $di->g(Translator::class));
    }
}
