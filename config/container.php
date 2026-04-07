<?php

use Infisical\SDK\InfisicalSDK;
use Jaxon\Di\Container;
use Lagdo\DbAdmin\Db;
use Lagdo\DbAdmin\Db\Config;
use Lagdo\DbAdmin\Db\Driver\Proxy;
use Lagdo\DbAdmin\Db\Service;
use Lagdo\DbAdmin\Support;
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
            $options = $di->g("dbadmin_server_options_$server");
            return Db\Driver\AppDriver::createDriver($di, $options);
        });
    }
});

return [
    'set' => [
        // Selected database driver options
        'dbadmin_server_options' => function(Container $di) {
            $server = $di->g('dbadmin_config_server');
            return $di->g("dbadmin_server_options_$server");
        },
        // Selected database driver
        Support\DriverInterface::class => function(Container $di) {
            $server = $di->g('dbadmin_config_server');
            return $di->g("dbadmin_server_$server");
        },
        // Proxies to the DB driver features
        Proxy\CommandProxy::class => function(Container $di) {
            $dbProxy = $di->g(Db\Driver\DbProxy::class);
            $timer = $di->g(Service\TimerService::class);
            $logger = $di->g(Service\Admin\QueryLogger::class);
            return new Proxy\CommandProxy($dbProxy, $timer, $logger);
        },
        Proxy\DatabaseProxy::class => function(Container $di) {
            $dbProxy = $di->g(Db\Driver\DbProxy::class);
            $options = $di->g('dbadmin_server_options');
            return new Proxy\DatabaseProxy($dbProxy, $options);
        },
        Proxy\ExportProxy::class => function(Container $di) {
            $dbProxy = $di->g(Db\Driver\DbProxy::class);
            return new Proxy\ExportProxy($dbProxy);
        },
        Proxy\ImportProxy::class => function(Container $di) {
            $dbProxy = $di->g(Db\Driver\DbProxy::class);
            $timer = $di->g(Service\TimerService::class);
            $logger = $di->g(Service\Admin\QueryLogger::class);
            return new Proxy\ImportProxy($dbProxy, $timer, $logger);
        },
        Proxy\QueryProxy::class => function(Container $di) {
            $dbProxy = $di->g(Db\Driver\DbProxy::class);
            return new Proxy\QueryProxy($dbProxy);
        },
        Proxy\SelectProxy::class => function(Container $di) {
            $dbProxy = $di->g(Db\Driver\DbProxy::class);
            $timer = $di->g(Service\TimerService::class);
            return new Proxy\SelectProxy($dbProxy, $timer);
        },
        Proxy\ServerProxy::class => function(Container $di) {
            $dbProxy = $di->g(Db\Driver\DbProxy::class);
            $options = $di->g('dbadmin_server_options');
            return new Proxy\ServerProxy($dbProxy, $options);
        },
        Proxy\TableProxy::class => function(Container $di) {
            $dbProxy = $di->g(Db\Driver\DbProxy::class);
            return new Proxy\TableProxy($dbProxy);
        },
        Proxy\UserProxy::class => function(Container $di) {
            $dbProxy = $di->g(Db\Driver\DbProxy::class);
            return new Proxy\UserProxy($dbProxy);
        },
        Proxy\ViewProxy::class => function(Container $di) {
            $dbProxy = $di->g(Db\Driver\DbProxy::class);
            return new Proxy\ViewProxy($dbProxy);
        },
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
        Support\Utils\Str::class,
        // The user input
        Support\Utils\Input::class,
        // The utils class
        Support\Utils\Utils::class,
        // The db classes
        Db\UiData\AppPage::class,
        // The proxy to the database features
        Db\Driver\DbProxy::class,
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
        Support\Utils\TranslatorInterface::class => Db\Translator::class,
    ],
];
