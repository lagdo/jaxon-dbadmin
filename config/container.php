<?php

use Jaxon\Config\ConfigSetter;
use Jaxon\Di\Container;
use Lagdo\DbAdmin\App\Ui;
use Lagdo\DbAdmin\Driver;
use Lagdo\DbAdmin\Support;
use Lagdo\DbAdmin\Support\DiAlias;
use Lagdo\DbAdmin\Support\Driver\DriverHelper;
use Lagdo\DbAdmin\Support\Driver\Proxy;
use Lagdo\DbAdmin\Support\Provider;
use Lagdo\DbAdmin\Support\Service;

$secrets = include  __DIR__ . '/secrets.php';

// This setup needs to be applied after the config is loaded.
jaxon()->callback()->boot(fn() => DriverHelper::setupDriver());

return [
    'val' => [
        'built_with' => 'Built on
<a style="text-decoration: none;" href="https://github.com/lagdo/dbadmin-mono" target="_blank">
    <i class="fa-brands fa-github"></i>
</a> using the &middot;
<a style="text-decoration: none;" href="https://www.jaxon-php.org" target="_blank">
    Jaxon library
</a> &middot;',
    ],
    'set' => [
        // Jaxon config setter. Already defined by the Jaxon library.
        // ConfigSetter::class => fn() => new ConfigSetter(),
        // Proxies to the DB driver features
        Support\Driver\QueryProcessor::class => fn(Container $di) =>
            (new Support\Driver\QueryProcessor($di->g(Support\Driver\DriverProxy::class)))
                ->setQuerySplitter($di->g(Service\Query\QuerySplitter::class))
                ->setQueryTimer($di->g(Service\Admin\QueryTimer::class))
                ->setQueryLogger($di->g(Service\Admin\QueryLogger::class)),
        Proxy\ServerProxy::class => fn(Container $di) =>
            (new Proxy\ServerProxy($di->g(Support\Driver\DriverProxy::class)))
                ->setOptions($di->g(DiAlias\ServerConfig::class)),
        Proxy\DatabaseProxy::class => fn(Container $di) =>
            (new Proxy\DatabaseProxy($di->g(Support\Driver\DriverProxy::class)))
                ->setProcessor($di->g(Support\Driver\QueryProcessor::class))
                ->setOptions($di->g(DiAlias\ServerConfig::class)),
        Proxy\ExportProxy::class => fn(Container $di) =>
            new Proxy\ExportProxy($di->g(Support\Driver\DriverProxy::class)),
        Proxy\QueryProxy::class => fn(Container $di) =>
            (new Proxy\QueryProxy($di->g(Support\Driver\DriverProxy::class)))
                ->setProcessor($di->g(Support\Driver\QueryProcessor::class))
                ->setPackageConfig($di->g(Support\DiAlias\PackageConfig::class)),
        Proxy\TableProxy::class => fn(Container $di) =>
            (new Proxy\TableProxy($di->g(Support\Driver\DriverProxy::class)))
                ->setProcessor($di->g(Support\Driver\QueryProcessor::class)),
        Proxy\SelectProxy::class => fn(Container $di) =>
            (new Proxy\SelectProxy($di->g(Support\Driver\DriverProxy::class)))
                ->setProcessor($di->g(Support\Driver\QueryProcessor::class))
                ->setPackageConfig($di->g(Support\DiAlias\PackageConfig::class)),

        // Application authentication.
        Provider\AuthInterface::class => fn(Container $di) =>
            $di->has(DiAlias\AuthInterface::class) ?
                // Custom auth service defined.
                $di->get(DiAlias\AuthInterface::class) :
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
            $config = $di->g(DiAlias\PackageConfig::class);
            $secretConfigReaderClass = $config->getOption('reader.secret',
                Provider\Config\SecretConfigProvider::class);
            $secretConfigReader = $di->get($secretConfigReaderClass);

            return new Provider\Config\ServerConfigProvider($secretConfigReader);
        },
        Provider\DatabaseConfigProvider::class => function(Container $di) {
            $config = $di->g(DiAlias\PackageConfig::class);
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
        Service\Admin\QueryTimer::class,
        // Database export
        Service\Export\DatabaseReader::class,
        // The UI builders
        Ui\AdminUiBuilder::class,
        Ui\AuditUiBuilder::class,
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
