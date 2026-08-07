<?php

namespace Lagdo\DbAdmin\Support\Provider\Secret;

use Google\ApiCore\ApiException;
use Google\Cloud\SecretManager\V1\AccessSecretVersionRequest;
use Google\Cloud\SecretManager\V1\Client\SecretManagerServiceClient;
use Lagdo\DbAdmin\Support\Provider\AuthInterface;
use Lagdo\Facades\Logger;
use RuntimeException;

class GcpSecretConfigProvider extends AbstractConfigProvider
{
    /**
     * @param AuthInterface $auth
     * @param SecretManagerServiceClient $secretServiceClient
     * @param string $projectId
     * @param string $version
     */
    public function __construct(AuthInterface $auth,
        private SecretManagerServiceClient $secretServiceClient,
        private string $projectId, private string $version)
    {
        parent::__construct($auth);
    }

    /**
     * @param string $secretKey
     *
     * @return string
     * @throws ApiException
     * @throws RuntimeException
     */
    private function getSecret(string $secretKey): string
    {
        $client = $this->secretServiceClient;

        // Build the resource name of the secret version.
        $secretName = $client->secretVersionName($this->projectId, $secretKey, $this->version);
        // Build the request.
        $request = AccessSecretVersionRequest::build($secretName);
        // Access the secret version.
        $response = $client->accessSecretVersion($request);

        return $response->getPayload()?->getData() ?? '';
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
        try {
            // The secret key is generated with the provided closure.
            $secretKey = $this->getSecretKey($prefix, $option);
            $secretValue = $this->getSecret($secretKey);
            if ($secretValue === '') {
                throw new RuntimeException("Secret retrieval failed: empty value");
            }

            return $secretValue;
        } catch (ApiException $e) {
            Logger::error('Failed to retrieve a secret from Google Secret Manager.', [
                'error' => $e->getMessage(),
            ]);
            throw new RuntimeException("Secret retrieval failed");
        }
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
