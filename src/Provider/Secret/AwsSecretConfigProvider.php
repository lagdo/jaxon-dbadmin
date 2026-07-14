<?php

namespace Lagdo\DbAdmin\Support\Provider\Secret;

use Aws\Exception\AwsException;
use Aws\SecretsManager\SecretsManagerClient;
use Lagdo\DbAdmin\Support\Provider\AuthInterface;
use Lagdo\Facades\Logger;
use Closure;
use RuntimeException;

use function json_decode;
use function json_last_error;

class AwsSecretConfigProvider extends SecretConfigProvider
{
    /**
     * @var Closure
     */
    private Closure $secretKeyBuilder;

    /**
     * @param AuthInterface $auth
     * @param SecretsManagerClient $secretServiceClient
     */
    public function __construct(private AuthInterface $auth,
        private SecretsManagerClient $secretServiceClient)
    {}

    /**
     * @param Closure $secretKeyBuilder
     *
     * @return self
     */
    public function setSecretKeyBuilder(Closure $secretKeyBuilder): self
    {
        $this->secretKeyBuilder = $secretKeyBuilder;
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
            $secret = $this->secretServiceClient->getSecretValue([
                'SecretId' => $secretName,
            ]);

            $secretString = $secret['SecretString'] ?? '';
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
    public function getCredentials(string $prefix): array
    {
        // The username and password are stored in the same json payload.
        // The secret name is generated with the provided closure using only the prefix.
        $secretName = ($this->secretKeyBuilder)($prefix, $this->auth);
        $secretData = $this->getSecret($secretName);
        if (!isset($secretData['username']) || !isset($secretData['password'])) {
            throw new RuntimeException("Secret retrieval failed: required field missing");
        }

        return [
            'username' => $secretData['username'],
            'password' => $secretData['password'],
        ];
    }
}
