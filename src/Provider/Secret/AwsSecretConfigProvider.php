<?php

namespace Lagdo\DbAdmin\Support\Provider\Secret;

use Aws\Exception\AwsException;
use Aws\SecretsManager\SecretsManagerClient;
use Lagdo\Facades\Logger;
use Lagdo\DbAdmin\Support\Provider\Config\SecretConfigProvider;
use RuntimeException;

use function json_decode;
use function json_last_error;

class AwsSecretConfigProvider extends SecretConfigProvider
{
    /**
     * @param KeyBuilderInterface $keyBuilder
     * @param SecretsManagerClient $secretServiceClient
     */
    public function __construct(private KeyBuilderInterface $keyBuilder,
        private SecretsManagerClient $secretServiceClient)
    {}

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
        $secretName = $this->keyBuilder->build($prefix);
        $secret = $this->getSecret($secretName);
        if (!isset($secret['username']) || !isset($secret['password'])) {
            throw new RuntimeException("Secret retrieval failed: required field missing");
        }

        return [
            'username' => $secret['username'],
            'password' => $secret['password'],
        ];
    }
}
