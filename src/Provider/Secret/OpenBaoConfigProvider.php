<?php

namespace Lagdo\DbAdmin\Support\Provider\Secret;

use Lagdo\Facades\Logger;
use Lagdo\DbAdmin\Support\Provider\Config\SecretConfigProvider;
use Psr\Http\Client\ClientExceptionInterface;
use Vault\Client;
use RuntimeException;

class OpenBaoConfigProvider extends SecretConfigProvider
{
    /**
     * @param KeyBuilderInterface $keyBuilder
     * @param Client $secretServiceClient
     * @param string $projectId
     */
    public function __construct(private KeyBuilderInterface $keyBuilder,
        private Client $secretServiceClient, private string $projectId)
    {}

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
            $secretKey = $this->keyBuilder->build($prefix, $option);
            $secret = $this->secretServiceClient
                ->read("/{$this->projectId}/$secretKey")
                ->getData(); // Raw array with secret's content.
            if (!isset($secret['data']['value'])) {
                throw new RuntimeException("Secret retrieval failed: empty value");
            }

            return $secret['data']['value'];
        } catch (ClientExceptionInterface $e) {
            Logger::error('Failed to retrieve a secret from OpenBao secret manager.', [
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
