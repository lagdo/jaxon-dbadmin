<?php

namespace Lagdo\DbAdmin\Support\Provider\Secret;

use ItkDev\AzureKeyVault\Exception\SecretException;
use ItkDev\AzureKeyVault\KeyVault\VaultSecret;
use Lagdo\Facades\Logger;
use Lagdo\DbAdmin\Support\Provider\Config\SecretConfigProvider;
use RuntimeException;

class AzureVaultConfigProvider extends SecretConfigProvider
{
    /**
     * @param KeyBuilderInterface $keyBuilder
     * @param VaultSecret $vaultSecret
     */
    public function __construct(private KeyBuilderInterface $keyBuilder,
        private VaultSecret $vaultSecret)
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
        $secretKey = $this->keyBuilder->build($prefix, $option);
        try {
            $secret = $this->vaultSecret->getSecret($secretKey, '');
        } catch (SecretException $e) {
            Logger::error('Failed to retrieve a secret from Azure Key Vault.', [
                'error' => $e->getMessage(),
            ]);
            throw new RuntimeException("Secret retrieval failed");
        }
        $value = $secret->getValue();
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
