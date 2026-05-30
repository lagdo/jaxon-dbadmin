<?php

use Aws\SecretsManager\SecretsManagerClient as AwsSecretManagerClient;
use Google\Cloud\SecretManager\V1\Client\SecretManagerServiceClient as GcpSecretManagerClient;
use GuzzleHttp\Client as HttpClient;
use Infisical\SDK\InfisicalSDK;
use Jaxon\App\View\ViewRenderer;
use Jaxon\Di\Container;
use Lagdo\DbAdmin\App\Ui;
use Lagdo\DbAdmin\Driver;
use Lagdo\DbAdmin\Support;
use Lagdo\DbAdmin\Support\Driver\Proxy;
use Lagdo\DbAdmin\Support\Provider;
use Lagdo\DbAdmin\Support\Service;
use Nyholm\Psr7\Factory\Psr17Factory;
use Vault\AuthenticationStrategies\AppRoleAuthenticationStrategy;
use Vault\AuthenticationStrategies\UserPassAuthenticationStrategy;
use Vault\AuthenticationStrategies\TokenAuthenticationStrategy;
use Vault\Client as OpenBaoClient;

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
            $di->has(Provider\AuthInterface::class) ?
                // Custom auth service defined.
                $di->get(Provider\AuthInterface::class) :
                // Default auth service when none is defined.
                new class implements Provider\AuthInterface {
                    public function user(): string
                    {
                        return '';
                    }
                    public function role(): string
                    {
                        return '';
                    }
                },
        Provider\PackageConfigProvider::class => fn(Container $di) =>
            new Provider\PackageConfigProvider($di->g('dbadmin_auth_service')),
        Provider\Config\AccessConfigProvider::class =>
            fn() => new Provider\Config\AccessConfigProvider(),
        Provider\Config\ServerConfigProvider::class => function(Container $di) {
            $config = $di->g('server_config_provider_options');
            $accessConfigReaderClass = $config->getOption('reader.access',
                Provider\Config\AccessConfigProvider::class);
            $accessConfigReader = $di->get($accessConfigReaderClass);

            return new Provider\Config\ServerConfigProvider($accessConfigReader);
        },
        Provider\DatabaseConfigProvider::class => function(Container $di) {
            $config = $di->g('server_config_provider_options');
            $serverConfigReaderClass = $config->getOption('reader.server',
                Provider\Config\ServerConfigProvider::class);
            $serverConfigReader = $di->get($serverConfigReaderClass);

            return new Provider\DatabaseConfigProvider($config, $serverConfigReader);
        },
        Provider\Secret\InfisicalConfigProvider::class => function(Container $di) {
            $auth = $di->get(Provider\AuthInterface::class);

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

            return new Provider\Secret\InfisicalConfigProvider($auth, $secrets,
                $projectId, $projectEnv, $secretPath);
        },
        Provider\Secret\AwsSecretConfigProvider::class => function(Container $di) {
            $auth = $di->get(Provider\AuthInterface::class);

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
            $client = new AwsSecretManagerClient([
                'region'      => env('AWS_SECRETS_REGION'),
                'version'     => env('AWS_SECRETS_VERSION'),
                'endpoint'    => env('AWS_SECRETS_SERVER_URL'),
                ...$awsAuth,
            ]);

            return new Provider\Secret\AwsSecretConfigProvider($auth, $client);
        },
        Provider\Secret\GcpSecretConfigProvider::class => function(Container $di) {
            $auth = $di->get(Provider\AuthInterface::class);

            $projectId = env('GCP_SECRETS_PROJECT_ID', '');
            $version = env('GCP_SECRETS_VERSION', 'latest');
            $credentials = env('GOOGLE_APPLICATION_CREDENTIALS');
            $endpoint = env('GCP_SECRETS_SERVER_URL');
            $options = [];
            if (($credentials)) {
                $options['credentials'] = $credentials;
            }
            if (($endpoint)) {
                $options['apiEndpoint'] = $endpoint;
            }
            $client = new GcpSecretManagerClient($options);

            return new Provider\Secret\GcpSecretConfigProvider($auth,
                $client, $projectId, $version);
        },
        Provider\Secret\OpenBaoConfigProvider::class => function(Container $di) {
            $authToken = env('OPENBAO_AUTH_TOKEN');
            $authUsername = env('OPENBAO_AUTH_USERNAME');
            $authPassword = env('OPENBAO_AUTH_PASSWORD');
            $authRoleId = env('OPENBAO_AUTH_ROLE_ID');
            $authSecretId = env('OPENBAO_AUTH_SECRET_ID');

            $authStrategy = match(true) {
                $authToken !== null => new TokenAuthenticationStrategy($authToken),
                $authUsername !== null && $authPassword !== null =>
                    new UserPassAuthenticationStrategy($authUsername, $authPassword),
                $authRoleId !== null && $authSecretId !== null =>
                    new AppRoleAuthenticationStrategy($authRoleId, $authSecretId),
                default => null,
            };
            if ($authStrategy === null) {
                throw new RuntimeException("No authentication strategy defined for the OpenBao Secret manager");
            }

            $auth = $di->get(Provider\AuthInterface::class);

            $httpClient = new HttpClient();
            // Reuse the PSR17 factory class from the jaxon-core library.
            $psr17Factory = $di->g(Psr17Factory::class);

            $namespace = env('OPENBAO_NAMESPACE');
            $projectId = env('OPENBAO_PROJECT_ID');
            $serverPath = env('OPENBAO_SERVER_PATH');

            // Creating the client
            $endpoint = $psr17Factory->createUri(env('OPENBAO_SERVER_URL'));
            $client = new OpenBaoClient($endpoint, $httpClient, $psr17Factory, $psr17Factory);
            if (($namespace)) {
                $client->setNamespace($namespace);
            }
            if (($serverPath)) {
                $client->setVersion($serverPath);
            }
            if (!$client->setAuthenticationStrategy($authStrategy)->authenticate()) {
                throw new RuntimeException("Authentication failure on the OpenBao Secret manager");;
            }

            return new Provider\Secret\OpenBaoConfigProvider($auth, $client, $projectId);
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
