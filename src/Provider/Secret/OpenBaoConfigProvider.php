<?php

namespace Lagdo\DbAdmin\Support\Provider\Secret;

use Lagdo\DbAdmin\Support\Provider\AuthInterface;
use Lagdo\Facades\Logger;
use Psr\Http\Client\ClientExceptionInterface;
use Vault\Client;
use Closure;
use RuntimeException;

class OpenBaoConfigProvider extends SecretConfigProvider
{
    /**
     * @var Closure
     */
    private Closure $secretKeyBuilder;

    /**
     * @param AuthInterface $auth
     * @param Client $secretServiceClient
     * @param string $projectId
     */
    public function __construct(private AuthInterface $auth,
        private Client $secretServiceClient, private string $projectId)
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
