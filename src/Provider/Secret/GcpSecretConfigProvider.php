<?php

namespace Lagdo\DbAdmin\Support\Provider\Secret;

use Google\ApiCore\ApiException;
use Google\Cloud\SecretManager\V1\AccessSecretVersionRequest;
use Google\Cloud\SecretManager\V1\Client\SecretManagerServiceClient;
use Lagdo\DbAdmin\Support\Provider\AuthInterface;
use Lagdo\DbAdmin\Support\Provider\Config\SecretConfigProvider;
use Lagdo\Facades\Logger;
use Closure;
use RuntimeException;

class GcpSecretConfigProvider extends SecretConfigProvider
{
    /**
     * @var Closure
     */
    private Closure $secretKeyBuilder;

    /**
     * @param AuthInterface $auth
     * @param SecretManagerServiceClient $secretServiceClient
     * @param string $projectId
     * @param string $version
     */
    public function __construct(private AuthInterface $auth,
        private SecretManagerServiceClient $secretServiceClient,
        private string $projectId, private string $version)
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
            $secretKey = ($this->secretKeyBuilder)($prefix, $option, $this->auth);
            $value = $this->getSecret($secretKey);
            if ($value === '') {
                throw new RuntimeException("Secret retrieval failed: empty value");
            }

            return $value;
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
