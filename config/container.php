<?php

use Jaxon\App\View\ViewRenderer;
use Jaxon\Config\ConfigSetter;
use Jaxon\Di\Container;
use Lagdo\DbAdmin\App\Ui;
use Lagdo\DbAdmin\Driver;
use Lagdo\DbAdmin\Support;
use Lagdo\DbAdmin\Support\Driver\DI;
use Lagdo\DbAdmin\Support\Driver\DriverHelper;
use Lagdo\DbAdmin\Support\Driver\Proxy;
use Lagdo\DbAdmin\Support\Provider;
use Lagdo\DbAdmin\Support\Service;

$secrets = include  __DIR__ . '/secrets.php';

// This setup needs to be applied after the config is loaded.
jaxon()->callback()->boot(function() {
    $di = jaxon()->di();

    // Register a driver for each database server.
    $configProvider = $di->g(Provider\DatabaseConfigProvider::class);
    foreach($configProvider->getServerIds() as $server) {
        // The driver options
        $di->set("dbadmin_server_options_$server", fn() =>
            $configProvider->getServerConfig($server));
        // The driver itself
        $di->set("dbadmin_server_$server", function() use($di, $server) {
            $utils = $di->g(Driver\Utils\Utils::class);
            $options = $di->g("dbadmin_server_options_$server");
            return Driver\Driver::createDriver($utils, $options);
        });
    }
    // Selected database driver options
    $di->set(DI\ServerConfig::class, function(Container $di) {
        $setter = $di->g(ConfigSetter::class);
        $server = $di->g('dbadmin_config_server');
        return $setter->newConfig($di->g("dbadmin_server_options_$server"));
    });
    // Selected database driver
    $di->set(DI\ServerDriver::class, function(Container $di) {
        $server = $di->g('dbadmin_config_server');
        return $di->g("dbadmin_server_$server");
    });

    // Make the translator available into views.
    $viewRenderer = $di->g(ViewRenderer::class);
    $viewRenderer->share('trans', $di->g(Support\Translator::class));
});

return [
    'set' => [
        // Jaxon config setter. Already defined by the Jaxon library.
        // ConfigSetter::class => fn() => new ConfigSetter(),
        // Proxies to the DB driver features
        Proxy\QueryProcessor::class => fn(Container $di) =>
            (new Proxy\QueryProcessor($di->g(Support\Driver\DriverProxy::class)))
                ->setQuerySplitter($di->g(Service\Query\QuerySplitter::class))
                ->setTimer($di->g(Service\TimerService::class))
                ->setQueryLogger($di->g(Service\Admin\QueryLogger::class)),
        Proxy\ServerProxy::class => fn(Container $di) =>
            (new Proxy\ServerProxy($di->g(Support\Driver\DriverProxy::class)))
                ->setOptions($di->g(DI\ServerConfig::class)),
        Proxy\DatabaseProxy::class => fn(Container $di) =>
            (new Proxy\DatabaseProxy($di->g(Support\Driver\DriverProxy::class)))
                ->setProcessor($di->g(Proxy\QueryProcessor::class))
                ->setOptions($di->g(DI\ServerConfig::class)),
        Proxy\ExportProxy::class => fn(Container $di) =>
            new Proxy\ExportProxy($di->g(Support\Driver\DriverProxy::class)),
        Proxy\QueryProxy::class => fn(Container $di) =>
            (new Proxy\QueryProxy($di->g(Support\Driver\DriverProxy::class)))
                ->setProcessor($di->g(Proxy\QueryProcessor::class))
                ->setPackageConfig($di->g(Support\Driver\DI\PackageConfig::class)),
        Proxy\TableProxy::class => fn(Container $di) =>
            (new Proxy\TableProxy($di->g(Support\Driver\DriverProxy::class)))
                ->setProcessor($di->g(Proxy\QueryProcessor::class)),
        Proxy\SelectProxy::class => fn(Container $di) =>
            (new Proxy\SelectProxy($di->g(Support\Driver\DriverProxy::class)))
                ->setProcessor($di->g(Proxy\QueryProcessor::class))
                ->setPackageConfig($di->g(Support\Driver\DI\PackageConfig::class)),

        // Application authentication.
        Provider\AuthInterface::class => fn(Container $di) =>
            $di->has('dbadmin_auth_service') ?
                // Custom auth service defined.
                $di->get('dbadmin_auth_service') :
                // Default auth service when none is defined.
                new class implements Provider\AuthInterface {
                    public function userId(): string
                    {
                        return '';
                    }
                    public function name(): string
                    {
                        return '';
                    }
                    public function roles(): array
                    {
                        return [];
                    }
                    public function audit(): string
                    {
                        return '';
                    }
                    public function logout(): string
                    {
                        return '';
                    }
                },
        Provider\PackageConfigProvider::class => fn(Container $di) =>
            new Provider\PackageConfigProvider($di->g(ConfigSetter::class),
                $di->g(Provider\AuthInterface::class)),
        Provider\Config\ServerConfigProvider::class => function(Container $di) {
            $config = $di->g(DI\PackageConfig::class);
            $secretConfigReaderClass = $config->getOption('reader.secret',
                Provider\Config\SecretConfigProvider::class);
            $secretConfigReader = $di->get($secretConfigReaderClass);

            return new Provider\Config\ServerConfigProvider($secretConfigReader);
        },
        Provider\DatabaseConfigProvider::class => function(Container $di) {
            $config = $di->g(DI\PackageConfig::class);
            $serverConfigReaderClass = $config->getOption('reader.server',
                Provider\Config\ServerConfigProvider::class);
            $serverConfigReader = $di->get($serverConfigReaderClass);

            return new Provider\DatabaseConfigProvider($config, $serverConfigReader);
        },
        ...$secrets,
    ],
    'auto' => [
        // Helper for the driver proxies.
        DriverHelper::class,
        // The string manipulation class
        Driver\Utils\Str::class,
        // The utils class
        Driver\Utils\Utils::class,
        // The PageUi class
        Support\Driver\PageUi::class,
        // The translator
        Support\Translator::class,
        // The proxy to the database features
        Support\Driver\DriverProxy::class,
        // The query splitter
        Service\Query\QuerySplitter::class,
        // The Breadcrumbs service
        Service\Breadcrumbs::class,
        // The Timer service
        Service\TimerService::class,
        // The UI builders
        Ui\AuditUiBuilder::class,
        Ui\UiBuilder::class,
        Ui\InputBuilder::class,
        Ui\MenuBuilder::class,
        Ui\Data\EditUiBuilder::class,
        Ui\Select\OptionsUiBuilder::class,
        Ui\Select\ResultUiBuilder::class,
        Ui\Select\SelectUiBuilder::class,
        Ui\Database\ServerUiBuilder::class,
        Ui\Command\QueryUiBuilder::class,
        Ui\Command\AuditUiBuilder::class,
        Ui\Command\ImportUiBuilder::class,
        Ui\Command\ExportUiBuilder::class,
        Ui\Table\TableUiBuilder::class,
        Ui\Table\ViewUiBuilder::class,
        Ui\Table\ColumnUiBuilder::class,
        Ui\Tab\Tab::class,
    ],
    'alias' => [
        // The translator
        Driver\Utils\TranslatorInterface::class => Support\Translator::class,
    ],
];
