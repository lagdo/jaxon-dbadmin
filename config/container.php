<?php

use Aws\SecretsManager\SecretsManagerClient;
use Infisical\SDK\InfisicalSDK;
use Jaxon\App\View\ViewRenderer;
use Jaxon\Di\Container;
use Lagdo\DbAdmin\App\Ui;
use Lagdo\DbAdmin\Driver;
use Lagdo\DbAdmin\Support;
use Lagdo\DbAdmin\Support\Config;
use Lagdo\DbAdmin\Support\Driver\Proxy;
use Lagdo\DbAdmin\Support\Service;

// This setup needs to be applied after the config is loaded.
jaxon()->callback()->boot(function() {
    $di = jaxon()->di();

    // Register a driver for each database server.
    $configProvider = $di->g(Config\DatabaseConfigProvider::class);
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
    $di->set('dbadmin_server_options', function(Container $di) {
        $server = $di->g('dbadmin_config_server');
        return $di->g("dbadmin_server_options_$server");
    });
    // Selected database driver
    $di->set('dbadmin_server_driver', function(Container $di) {
        $server = $di->g('dbadmin_config_server');
        return $di->g("dbadmin_server_$server");
    });

    // Make the translator available into views.
    $viewRenderer = $di->g(ViewRenderer::class);
    $viewRenderer->share('trans', $di->g(Support\Translator::class));
});

return [
    'set' => [
        // Proxies to the DB driver features
        Proxy\CommandProxy::class => fn(Container $di) =>
            (new Proxy\CommandProxy($di->g(Support\Driver\DriverProxy::class)))
                ->setTimer($di->g(Service\TimerService::class))
                ->setQueryLogger($di->g(Service\Admin\QueryLogger::class)),
        Proxy\DatabaseProxy::class => fn(Container $di) =>
            (new Proxy\DatabaseProxy($di->g(Support\Driver\DriverProxy::class)))
                ->setOptions($di->g('dbadmin_server_options')),
        Proxy\ExportProxy::class => fn(Container $di) =>
            new Proxy\ExportProxy($di->g(Support\Driver\DriverProxy::class)),
        Proxy\QueryProxy::class => fn(Container $di) =>
            new Proxy\QueryProxy($di->g(Support\Driver\DriverProxy::class)),
        Proxy\SelectProxy::class => fn(Container $di) =>
            (new Proxy\SelectProxy($di->g(Support\Driver\DriverProxy::class)))
                ->setTimer($di->g(Service\TimerService::class)),
        Proxy\ServerProxy::class => fn(Container $di) =>
            (new Proxy\ServerProxy($di->g(Support\Driver\DriverProxy::class)))
                ->setOptions($di->g('dbadmin_server_options')),
        Proxy\TableProxy::class => fn(Container $di) =>
            new Proxy\TableProxy($di->g(Support\Driver\DriverProxy::class)),

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
        Config\PackageConfigProvider::class => fn(Container $di) =>
            new Config\PackageConfigProvider($di->g('dbadmin_auth_service')),
        Config\Server\AccessConfigProvider::class => fn() => new Config\Server\AccessConfigProvider(),
        Config\Server\ServerConfigProvider::class => function(Container $di) {
            $config = $di->g('server_config_provider_options');
            $accessConfigReaderClass = $config->getOption('reader.access',
                Config\Server\AccessConfigProvider::class);
            $accessConfigReader = $di->get($accessConfigReaderClass);

            return new Config\Server\ServerConfigProvider($accessConfigReader);
        },
        Config\DatabaseConfigProvider::class => function(Container $di) {
            $config = $di->g('server_config_provider_options');
            $serverConfigReaderClass = $config->getOption('reader.server',
                Config\Server\ServerConfigProvider::class);
            $serverConfigReader = $di->get($serverConfigReaderClass);

            return new Config\DatabaseConfigProvider($config, $serverConfigReader);
        },
        Config\Server\InfisicalConfigProvider::class => function(Container $di) {
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

            return new Config\Server\InfisicalConfigProvider($auth, $secrets,
                $projectId, $projectEnv, $secretPath);
        },
        Config\Server\AwsSecretsConfigProvider::class => function(Container $di) {
            $auth = $di->get(Config\AuthInterface::class);

            $awsAuth = match(env('AWS_SECRETS_CLIENT_AUTH')) {
                'credentials' => [
                    'credentials' => [
                        'key'    => env('AWS_SECRETS_CLIENT_KEY'),
                        'secret' => env('AWS_SECRETS_CLIENT_SECRET'),
                    ]
                ],
                'profile' => [
                    'profile' => env('AWS_SECRETS_CLIENT_PROFILE'),
                ],
                default => [],
            };
            $client = new SecretsManagerClient([
                'region'      => env('AWS_SECRETS_REGION'),
                'version'     => env('AWS_SECRETS_VERSION'),
                'endpoint'    => env('AWS_SECRETS_SERVER_URL'),
                ...$awsAuth,
            ]);

            return new Config\Server\AwsSecretsConfigProvider($auth, $client);
        },
    ],
    'auto' => [
        // The string manipulation class
        Driver\Utils\Str::class,
        // The user input
        Driver\Utils\Input::class,
        // The utils class
        Driver\Utils\Utils::class,
        // The PageUi class
        Support\Driver\PageUi::class,
        // The translator
        Support\Translator::class,
        // The proxy to the database features
        Support\Driver\DriverProxy::class,
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
    'extend' => [],
];
