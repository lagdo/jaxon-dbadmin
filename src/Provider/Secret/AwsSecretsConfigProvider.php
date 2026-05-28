<?php

namespace Lagdo\DbAdmin\Support\Provider\Secret;

use Aws\Exception\AwsException;
use Aws\SecretsManager\SecretsManagerClient;
use Lagdo\DbAdmin\Support\Provider\AuthInterface;
use Lagdo\DbAdmin\Support\Provider\Config\AccessConfigProvider;
use Lagdo\Facades\Logger;
use Closure;
use RuntimeException;

use function json_decode;
use function json_last_error;

class AwsSecretsConfigProvider extends AccessConfigProvider
{
    /**
     * @var Closure
     */
    private Closure $secretNameBuilder;

    /**
     * @param AuthInterface $auth
     * @param SecretsManagerClient $secretsManager
     */
    public function __construct(private AuthInterface $auth,
        private SecretsManagerClient $secretsManager)
    {}

    /**
     * @param Closure $secretNameBuilder
     *
     * @return self
     */
    public function setSecretNameBuilder(Closure $secretNameBuilder): self
    {
        $this->secretNameBuilder = $secretNameBuilder;
        return $this;
    }

    /**
     * @param string $secretName
     *
     * @return array
     * @throws RuntimeException
     */
    private function getSecret(string $secretName): array
    {
        try {
            $result = $this->secretsManager->getSecretValue([
                'SecretId' => $secretName,
            ]);

            $secretString = $result['SecretString'] ?? '';
            if (empty($secretString)) {
                throw new RuntimeException("Secret retrieval failed: empty response");
            }
            $decoded = json_decode($secretString, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException("Secret retrieval failed: invalid format");
            }

            return $decoded;
        } catch (AwsException $e) {
            Logger::error('Failed to retrieve a secret from AWS Secrets Manager.', [
                'error' => $e->getMessage(),
            ]);
            throw new RuntimeException("Secret retrieval failed");
        }
    }

    /**
     * @param string $prefix
     *
     * @return array
     */
    public function readAccessConfig(string $prefix): array
    {
        $options = [];
        if(($host = $this->getHost($prefix)) !== '') {
            $options['host'] = $host;
        }
        if ($this->config()->hasOption("$prefix.port")) {
            $options['port'] = $this->getPort($prefix);
        }

        // The username and password are stored in the same json payload.
        // The secret name is generated with the provided closure using only the prefix.
        $secretName = ($this->secretNameBuilder)($prefix, $this->auth);
        $secretData = $this->getSecret($secretName);
        if (!isset($secretData['username']) || !isset($secretData['password'])) {
            throw new RuntimeException("Secret retrieval failed: required field missing");
        }

        return [
            ...$options,
            'username' => $secretData['username'],
            'password' => $secretData['password'],
        ];
    }
}
