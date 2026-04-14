<?php

use Infisical\SDK\InfisicalSDK;
use Jaxon\Di\Container;
use Lagdo\DbAdmin\Db;
use Lagdo\DbAdmin\Db\Config;
use Lagdo\DbAdmin\Db\Driver\Proxy;
use Lagdo\DbAdmin\Db\Service;
use Lagdo\DbAdmin\Driver;
use Lagdo\DbAdmin\Ui;

// This setup needs to be applied after the config is loaded.
jaxon()->callback()->boot(function() {
    $di = jaxon()->di();

    // Register a driver for each database server.
    $serverConfig = $di->g(Config\ServerConfig::class);
    foreach($serverConfig->getServerIds() as $server) {
        // The driver options
        $di->set("dbadmin_server_options_$server", fn() =>
            $serverConfig->getServerConfig($server));
        // The driver itself
        $di->set("dbadmin_server_$server", function() use($di, $server) {
            $utils = $di->g(Driver\Utils\Utils::class);
            $options = $di->g("dbadmin_server_options_$server");
            return Driver\Driver::createDriver($utils, $options);
        });
    }
    // Selected database driver options
    $di->set('dbadmin_server_options', function(Container $di) {
        $server = $di->g('dbadmin_config_server');
        return $di->g("dbadmin_server_options_$server");
    });
    // Selected database driver
    $di->set('dbadmin_server_driver', function(Container $di) {
        $server = $di->g('dbadmin_config_server');
        return $di->g("dbadmin_server_$server");
    });
});

return [
    'set' => [
        // The AppPage class
        Db\UiData\AppPage::class => fn(Container $di) =>
            new Db\UiData\AppPage($di->g(Db\Driver\DriverProxy::class)->helper()),
        // Proxies to the DB driver features
        Proxy\CommandProxy::class => fn(Container $di) =>
            (new Proxy\CommandProxy($di->g(Db\Driver\DriverProxy::class)->helper()))
                ->setTimer($di->g(Service\TimerService::class))
                ->setQueryLogger($di->g(Service\Admin\QueryLogger::class)),
        Proxy\DatabaseProxy::class => fn(Container $di) =>
            (new Proxy\DatabaseProxy($di->g(Db\Driver\DriverProxy::class)->helper()))
                ->setOptions($di->g('dbadmin_server_options')),
        Proxy\ExportProxy::class => fn(Container $di) =>
            new Proxy\ExportProxy($di->g(Db\Driver\DriverProxy::class)->helper()),
        Proxy\ImportProxy::class => fn(Container $di) =>
            (new Proxy\ImportProxy($di->g(Db\Driver\DriverProxy::class)->helper()))
                ->setTimer($di->g(Service\TimerService::class))
                ->setQueryLogger($di->g(Service\Admin\QueryLogger::class)),
        Proxy\QueryProxy::class => fn(Container $di) =>
            new Proxy\QueryProxy($di->g(Db\Driver\DriverProxy::class)->helper()),
        Proxy\SelectProxy::class => fn(Container $di) =>
            (new Proxy\SelectProxy($di->g(Db\Driver\DriverProxy::class)->helper()))
                ->setTimer($di->g(Service\TimerService::class)),
        Proxy\ServerProxy::class => fn(Container $di) =>
            (new Proxy\ServerProxy($di->g(Db\Driver\DriverProxy::class)->helper()))
                ->setOptions($di->g('dbadmin_server_options')),
        Proxy\TableProxy::class => fn(Container $di) =>
            new Proxy\TableProxy($di->g(Db\Driver\DriverProxy::class)->helper()),
        Proxy\UserProxy::class => fn(Container $di) =>
            new Proxy\UserProxy($di->g(Db\Driver\DriverProxy::class)->helper()),
        Proxy\ViewProxy::class => fn(Container $di) =>
            new Proxy\ViewProxy($di->g(Db\Driver\DriverProxy::class)->helper()),

        // Application authentication.
        'dbadmin_auth_service' => fn(Container $di) =>
            $di->has(Config\AuthInterface::class) ?
                // Custom auth service defined.
                $di->get(Config\AuthInterface::class) :
                // Default auth service when none is defined.
                new class implements Config\AuthInterface {
                    public function user(): string
                    {
                        return '';
                    }
                    public function role(): string
                    {
                        return '';
                    }
                },
        Config\ConfigProvider::class => fn(Container $di) =>
            new Config\ConfigProvider($di->g('dbadmin_auth_service')),
        Config\ConfigReader::class => fn() => new Config\ConfigReader(),
        Config\InfisicalConfigReader::class => function(Container $di) {
            $auth = $di->get(Config\AuthInterface::class);

            $infisicalSdk = new InfisicalSDK(env('INFISICAL_SERVER_URL'));
            $clientId = env('INFISICAL_MACHINE_CLIENT_ID');
            $clientSecret = env('INFISICAL_MACHINE_CLIENT_SECRET');
            // Authenticate on the Infisical server.
            $infisicalSdk->auth()->universalAuth()->login($clientId, $clientSecret);
            // Create the Infisical secrets service.
            $secrets = $infisicalSdk->secrets();
            $projectId = env('INFISICAL_PROJECT_ID');
            $projectEnv = env('INFISICAL_PROJECT_ENV', 'dev');
            $secretPath = env('INFISICAL_SECRET_PATH', '');

            return new Config\InfisicalConfigReader($auth, $secrets,
                $projectId, $projectEnv, $secretPath);
        },
    ],
    'auto' => [
        // The translator
        Db\Translator::class,
        // The string manipulation class
        Driver\Utils\Str::class,
        // The user input
        Driver\Utils\Input::class,
        // The utils class
        Driver\Utils\Utils::class,
        // The proxy to the database features
        Db\Driver\DriverProxy::class,
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
    ],
    'alias' => [
        // The translator
        Driver\Utils\TranslatorInterface::class => Db\Translator::class,
    ],
];
