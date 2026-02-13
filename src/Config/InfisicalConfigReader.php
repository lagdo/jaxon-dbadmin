<?php

namespace Lagdo\DbAdmin\Db\Config;

use Infisical\SDK\Models\GetSecretParameters;
use Infisical\SDK\Models\Secret;
use Infisical\SDK\Services\SecretsService;
use Closure;

class InfisicalConfigReader extends ConfigReader
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
     */
    private function getSecret(string $secretKey): Secret
    {
        $params = [
            'secretKey' => $secretKey,
            'environment' => $this->environment,
            'projectId' => $this->projectId,
        ];
        // Add the secretPath only if one is provided.
        if ($this->secretPath !== '') {
            $params['secretPath'] = $this->secretPath;
        }
        return $this->secrets->get(new GetSecretParameters(...$params));
    }

    /**
     * @param string $prefix
     * @param string $option
     *
     * @return string
     */
    private function getSecretValue(string $prefix, string $option): string
    {
        // The secret key is generated with the provided closure.
        $secretKey = ($this->secretKeyBuilder)($prefix, $option, $this->auth);
        return $this->getSecret($secretKey)->secretValue;
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
