<?php

namespace Lagdo\DbAdmin\Support\Provider\Secret;

use Infisical\SDK\Models\GetSecretParameters;
use Infisical\SDK\Models\Secret;
use Infisical\SDK\Services\SecretsService;
use Lagdo\DbAdmin\Support\Provider\AuthInterface;
use Exception;
use RuntimeException;

class InfisicalConfigProvider extends AbstractConfigProvider
{
    /**
     * @param AuthInterface $auth
     * @param SecretsService $secretServiceClient
     * @param string $projectId
     * @param string $environment
     * @param string $secretPath
     */
    public function __construct(AuthInterface $auth,
        private SecretsService $secretServiceClient, private string $projectId,
        private string $environment, private string $secretPath)
    {
        parent::__construct($auth);
    }

    /**
     * Query the Infisical server for a secret.
     *
     * @param string $secretKey
     *
     * @return Secret
     * @throws RuntimeException
     */
    private function getSecret(string $secretKey): Secret
    {
        try {
            $params = [
                'secretKey' => $secretKey,
                'environment' => $this->environment,
                'projectId' => $this->projectId,
            ];
            // Add the secretPath only if one is provided.
            if ($this->secretPath !== '') {
                $params['secretPath'] = $this->secretPath;
            }
            $secret = $this->secretServiceClient->get(new GetSecretParameters(...$params));
            if (!$secret || !isset($secret->secretValue)) {
                throw new RuntimeException("Secret retrieval failed: invalid response");
            }

            return $secret;
        } catch (Exception $e) {
            throw new RuntimeException("Secret retrieval failed");
        }
    }

    /**
     * @param string $prefix
     * @param string $option
     *
     * @return string
     * @throws RuntimeException
     */
    private function getSecretValue(string $prefix, string $option): string
    {
        // The secret key is generated with the provided closure.
        $secretKey = $this->getSecretKey($prefix, $option);
        $secret = $this->getSecret($secretKey);
        $value = $secret->secretValue ?? '';
        if ($value === '') {
            throw new RuntimeException("Secret retrieval failed: empty value");
        }

        return $value;
    }

    /**
     * @inheritDoc
     */
    protected function getUsername(string $prefix): string
    {
        return $this->getSecretValue($prefix, 'username');
    }

    /**
     * @inheritDoc
     */
    protected function getPassword(string $prefix): string
    {
        return $this->getSecretValue($prefix, 'password');
    }
}
