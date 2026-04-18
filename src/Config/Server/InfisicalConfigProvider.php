<?php

namespace Lagdo\DbAdmin\Support\Config\Server;

use Infisical\SDK\Models\GetSecretParameters;
use Infisical\SDK\Models\Secret;
use Infisical\SDK\Services\SecretsService;
use Lagdo\DbAdmin\Support\Config\AuthInterface;
use Closure;
use Exception;
use RuntimeException;

class InfisicalConfigProvider extends AccessConfigProvider
{
    /**
     * @var Closure
     */
    private Closure $secretKeyBuilder;

    /**
     * @param AuthInterface $auth
     * @param SecretsService $secrets
     * @param string $projectId
     * @param string $environment
     * @param string $secretPath
     */
    public function __construct(private AuthInterface $auth,
        private SecretsService $secrets, private string $projectId,
        private string $environment, private string $secretPath)
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
            $secret = $this->secrets->get(new GetSecretParameters(...$params));
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
        $secretKey = ($this->secretKeyBuilder)($prefix, $option, $this->auth);
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
