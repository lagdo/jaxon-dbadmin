<?php

use Aws\SecretsManager\SecretsManagerClient as AwsSecretManagerClient;
use Google\Cloud\SecretManager\V1\Client\SecretManagerServiceClient as GcpSecretManagerClient;
use GuzzleHttp\Client as HttpClient;
use Infisical\SDK\InfisicalSDK;
use Jaxon\Di\Container;
use Lagdo\DbAdmin\Support\Provider\AuthInterface;
use Lagdo\DbAdmin\Support\Provider\Secret;
use Nyholm\Psr7\Factory\Psr17Factory;
use Vault\AuthenticationStrategies\AppRoleAuthenticationStrategy;
use Vault\AuthenticationStrategies\UserPassAuthenticationStrategy;
use Vault\AuthenticationStrategies\TokenAuthenticationStrategy;
use Vault\Client as OpenBaoClient;

return [
    Secret\InfisicalConfigProvider::class => function(Container $di) {
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

        $auth = $di->get(AuthInterface::class);
        return new Secret\InfisicalConfigProvider($auth, $secrets,
            $projectId, $projectEnv, $secretPath);
    },
    Secret\AwsSecretConfigProvider::class => function(Container $di) {
        $profile = env('AWS_SECRETS_CLIENT_PROFILE');
        $clientKey = env('AWS_SECRETS_CLIENT_KEY');
        $clientSecret = env('AWS_SECRETS_CLIENT_SECRET');
        $awsAuth = match(true) {
            $profile !== null => [
                'profile' => $profile,
            ],
            $clientKey !== null && $clientSecret !== null => [
                'credentials' => [
                    'key' => $clientKey,
                    'secret' => $clientSecret,
                ],
            ],
            default => [],
        };
        $client = new AwsSecretManagerClient([
            'region'      => env('AWS_SECRETS_REGION'),
            'version'     => env('AWS_SECRETS_VERSION'),
            'endpoint'    => env('AWS_SECRETS_SERVER_URL'),
            ...$awsAuth,
        ]);

        $auth = $di->get(AuthInterface::class);
        return new Secret\AwsSecretConfigProvider($auth, $client);
    },
    Secret\GcpSecretConfigProvider::class => function(Container $di) {
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

        $auth = $di->get(AuthInterface::class);
        return new Secret\GcpSecretConfigProvider($auth,
            $client, $projectId, $version);
    },
    Secret\OpenBaoConfigProvider::class => function(Container $di) {
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

        $namespace = env('OPENBAO_NAMESPACE');
        $projectId = env('OPENBAO_PROJECT_ID');
        $serverPath = env('OPENBAO_SERVER_PATH');

        // Creating the client
        $httpClient = new HttpClient();
        // Reuse the PSR17 factory class from the jaxon-core library.
        $psr17Factory = $di->g(Psr17Factory::class);
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

        $auth = $di->get(AuthInterface::class);
        return new Secret\OpenBaoConfigProvider($auth, $client, $projectId);
    },
];
